<?php

namespace App\Http\Controllers\API;

use App\Exports\ProductExcelExport;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Imports\ProductImport;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Auth;


class ProductAPIController extends AppBaseController
{
    /** @var ProductRepository */
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    //     dd (Auth::user());
    //   if (!auth()->user()->can('manage.product') ) {
    //   return   $this->sendError('Permission Denied');
    //   } 
    }

    /**
     * @param  Request  $request
     * @return ProductCollection
     */
    public function index(Request $request)
    {
      if (!Auth::user()->can("product.view")) {
        return $this->sendError('Permission Denied');
      }
        $perPage = getPageSize($request);
        $products = $this->productRepository;


        if ($request->get('product_unit')) {
            $products->where('product_unit', $request->get('product_unit'));
        }

        if ($request->get('warehouse_id') && $request->get('warehouse_id') != 'null') {
            $warehouseId = $request->get('warehouse_id');
            $products->whereHas('stock', function ($q) use ($warehouseId) {
                $q->where('manage_stocks.warehouse_id', $warehouseId);
            })->with([
                'stock' => function (HasOne $query) use ($warehouseId) {
                    $query->where('manage_stocks.warehouse_id', $warehouseId);
                },
            ]);
        }

        $products = $products->paginate($perPage);
        ProductResource::usingWithCollection();

        return new ProductCollection($products);
    }

    /**
     * @param  CreateProductRequest  $request
     * @return ProductResource|JsonResponse
     */
    public function store(CreateProductRequest $request)
    {
        if (!Auth::user()->can("product.create")) {
            return $this->sendError('Permission Denied');
          }
        $input = $request->all();
        // if ($input['barcode_symbol'] == Product::EAN8 && strlen($input['code']) != 7) {
        //     return $this->sendError('Please enter 7 digit code');
        // }

        // if ($input['barcode_symbol'] == Product::UPC && strlen($input['code']) != 11) {
        //     return $this->sendError(' Please enter 11 digit code');
        // }

        $product = $this->productRepository->storeProduct($input);

        return new ProductResource($product);
    }

    /**
     * @param $id
     * @return ProductResource
     */
    public function show($id)
    {
        if (!Auth::user()->can("product.view")) {
            return $this->sendError('Permission Denied');
          }
        $product = $this->productRepository->find($id);

        return new ProductResource($product);
    }

    /**
     * @param  UpdateProductRequest  $request
     * @param $id
     * @return ProductResource
     */
    public function update(UpdateProductRequest $request, $id)
    {
        if (!Auth::user()->can("product.edit")) {
            return $this->sendError('Permission Denied');
          }
        $input = $request->all();
        $product = $this->productRepository->updateProduct($input, $id);

        return new ProductResource($product);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!Auth::user()->can("product.delete")) {
            return $this->sendError('Permission Denied');
          }
        $purchaseItemModels = [
            PurchaseItem::class,
        ];
        $saleItemModels = [
            SaleItem::class,
        ];
        $purchaseResult = canDelete($purchaseItemModels, 'product_id', $id);
        $saleResult = canDelete($saleItemModels, 'product_id', $id);
        if ($purchaseResult || $saleResult) {
            return $this->sendError(__('messages.error.product_cant_deleted'));
        }

        if (File::exists(Storage::path('product_barcode/barcode-PR_'.$id.'.png'))) {
            File::delete(Storage::path('product_barcode/barcode-PR_'.$id.'.png'));
        }

        $this->productRepository->delete($id);

        return $this->sendSuccess('Product deleted successfully');
    }

    /**
     * @param $mediaId
     * @return JsonResponse
     */
    public function productImageDelete($mediaId): JsonResponse
    {
        $media = Media::where('id', $mediaId)->firstOrFail();
        $media->delete();

        return $this->sendSuccess('Product image deleted successfully');
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function importProducts(Request $request)
    {
        Excel::import(new ProductImport, request()->file('file'));

        return $this->sendSuccess('Products imported successfully');
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getProductExportExcel(Request $request): JsonResponse
    {
        if (Storage::exists('excel/product-excel-export.xlsx')) {
            Storage::delete('excel/product-excel-export.xlsx');
        }
        Excel::store(new ProductExcelExport, 'excel/product-excel-export.xlsx');

        $data['product_excel_url'] = Storage::url('excel/product-excel-export.xlsx');

        return $this->sendResponse($data, 'Product retrieved successfully');
    }
}
