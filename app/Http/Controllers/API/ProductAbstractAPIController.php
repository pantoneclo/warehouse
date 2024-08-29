<?php

namespace App\Http\Controllers\API;

use App\Exports\ProductExcelExport;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateProductAbstractRequest;
use App\Http\Requests\UpdateProductAbstractRequest;
use App\Http\Resources\ProductAbstractResource;
use App\Http\Resources\ProductAbstractCollection;
use App\Imports\ProductImport;
use App\Models\ProductAbstract;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Repositories\ProductAbstractRepository;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Auth;
class ProductAbstractAPIController extends AppBaseController
{
    public function __construct(ProductAbstractRepository $productAbstractRepository)
    {
        $this->productAbstractRepository = $productAbstractRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can("manage.products")) {
            return $this->sendError('Permission Denied');
          }
       
        $perPage = getPageSize($request);
        $productAbstract = $this->productAbstractRepository;
        $productAbstract = $productAbstract->paginate($perPage);
        ProductAbstractResource::usingWithCollection();

        return new ProductAbstractCollection($productAbstract);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateProductAbstractRequest $request)
    {
        if (!Auth::user()->can("product.create")) {
            return $this->sendError('Permission Denied');
          }
        $input = $request->all();
        $productAbstract = $this->productAbstractRepository->storeAbstract($input);

        return new ProductAbstractResource($productAbstract);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!Auth::user()->can("product.view")) {
            return $this->sendError('Permission Denied');
          }
        $productAbstract = $this->productAbstractRepository->find($id);

        return new ProductAbstractResource($productAbstract);
    }

    public function update(UpdateProductAbstractRequest $request, $id)
    {
        if (!Auth::user()->can("product.edit")) {
            return $this->sendError('Permission Denied');
          }
        $input = $request->all();
        $productAbstract = $this->productAbstractRepository->updateAbstract($input, $id);

        return new ProductAbstractResource($productAbstract);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {if (!Auth::user()->can("product.delete")) {
        return $this->sendError('Permission Denied');
      }
        $this->productAbstractRepository->delete($id);

        return $this->sendSuccess('product abstract deleted successfully');
    }

    /**
     * @param $mediaId
     * @return JsonResponse
     */
    public function productAbstractImageDelete($mediaId): JsonResponse
    {
        $media = Media::where('id', $mediaId)->firstOrFail();
        $media->delete();

        return $this->sendSuccess('product abstract deleted successfully');
    }
}
