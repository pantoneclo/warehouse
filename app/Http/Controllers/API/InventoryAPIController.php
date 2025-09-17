<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Resources\InventoryCollection;
use App\Http\Resources\InventoryResource;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Inventory;
use App\Models\InventoryCombo;
use App\Models\Product;
use App\Repositories\InventoryRepository;
use App\Repositories\ProductRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Picqer\Barcode\BarcodeGeneratorPNG;


class InventoryAPIController extends AppBaseController
{
    private $inventoryRepository;
    private $productRepository;

    public function __construct(InventoryRepository $inventoryRepository, ProductRepository $productRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
        $this->productRepository   = $productRepository;
    }

    public function index(Request $request)
    {
        $perPage = getPageSize($request);
        $sort    = NULL;
        if ( $request->sort == 'product_count' ) {
            $sort = 'asc';
            $request->request->remove('sort');
        } else if ( $request->sort == '-product_count' ) {
            $sort = 'desc';
            $request->request->remove('sort');
        }
        $inventories = $this->inventoryRepository->with(['combo', 'combo.product'])->when($sort,
            function ($q) use ($sort) {
                $q->orderBy('id', $sort);
            })->groupBy('insert_key')->orderBy('id', 'asc')->paginate($perPage);
        InventoryResource::withoutWrapping();
        InventoryResource::usingWithCollection();

        return new InventoryCollection($inventories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items'                       => 'required|array',
            'items.*.no_of_items_per_box' => 'required|integer|min:0',
            'items.*.no_boxes'            => 'required|integer|min:0',
            'items.*.net_wt'              => 'required',
            'items.*.gross_wt'            => 'required',
            'items.*.carton_meas'         => 'required',
        ]);

        if ( $validator->fails() ) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->input('items');
            DB::beginTransaction();
            $prodIdst = [];

            foreach ( $data as $item ) {

                $inventory = Inventory::create([
                    'no_of_items_per_box' => $item['no_of_items_per_box'],
                    'no_of_boxes'         => $item['no_boxes'],
                    'sticker_meas_unit'   => $item['sticker_meas_unit'],
                    'net_wt'              => $item['net_wt'],
                    'gross_wt'            => $item['gross_wt'],
                    'carton_meas'         => $item['carton_meas'],
                    'created_by'          => Auth::user()->id,
                    'insert_key'          => $this->generateUniqueKey()
                ]);


                $products = $item['product'];
                foreach ( $products as $product ) {
                    $single_product = Product::find($product['product_id']);
                    if (!$single_product) {
                        throw new \Exception("Product not found: {$product['product_id']}");
                    }
                    // Sanitize the product name
                    $productName = preg_replace('/[\r\n]+/', ' ',$product['style']);
                    $reference_code = 'sticker_' . $single_product->code;
                    $this->generateBarcode($productName, $reference_code);
                    $barcode_image_url = '/uploads/sticker_barcode/barcode-' . $reference_code . '.png';

                    InventoryCombo::create([
                        'inventory_id'  => $inventory->id,
                        'product_id'    => $product['product_id'],
                        'barcode_image' => $barcode_image_url,
                        'item_per_box'  => $product['item_per_box'],
                        'variant_id'  => $product['variant_id'],
                        'color'  => $product['variant_color'],
                        'size'  => $product['variant_size'],
                        'style'  => $product['style'],
                        'sticker_id' => $this->generateUniqueSticker()
                    ]);
                }
            }


            DB::commit();
            return response()->json([
                'status'  => TRUE,
                'message' => 'Inventory items created successfully',
                'data'    => $inventory,
            ]);
        }
        catch ( \Exception $e ) {
            DB::rollBack();
            Log::error('Error creating inventory: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while creating inventory items.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function generateUniqueKey()
    {
        // Get the current date and time
        $currentDateTime = Carbon::now()->format('YmdHis');

        // Get the authenticated user's ID
        $userId = Auth::id();

        // Generate the unique key
        $uniqueKey = $currentDateTime . '_' . $userId;

        return $uniqueKey;
    }
    public function generateUniqueSticker()
    {
        // Get the current date and time
        $currentDateTime = Carbon::now()->format('YmdHis');

        // Get the authenticated user's ID
        $userId = Auth::id();
        $rand = rand(100,10000);
        // Generate the unique key
        $uniqueKey = $currentDateTime . '_' . $rand;

        return "PK_".$uniqueKey;
    }

    public static function generateBarcode($productName, $reference_code)
    {
        $generator   = new BarcodeGeneratorPNG();
        $barcodeType = $generator::TYPE_CODE_128;
        Storage::disk(config('app.media_disc'))->put('sticker_barcode/barcode-' . $reference_code . '.png',
            $generator->getBarcode($productName, $barcodeType, 4, 70));
        return TRUE;
    }

    public function show($id)
    {
        $inventory = $this->inventoryRepository->with(['combo', 'combo.product'])->find($id);

        if (!$inventory) {
            return response()->json([
                'status' => false,
                'message' => 'Inventory not found'
            ], 404);
        }

        InventoryResource::withoutWrapping();
        return new InventoryResource($inventory);
    }

    public function update($insert_key, Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'inventories' => 'required|array',
                'inventories.*.id' => 'required|integer',
                'inventories.*.sticker_meas_unit' => 'required|string',
                'inventories.*.no_of_boxes' => 'required|integer|min:0',
                'inventories.*.net_wt' => 'required|string',
                'inventories.*.gross_wt' => 'required|string',
                'inventories.*.carton_meas' => 'required|string',
                'inventories.*.combos' => 'required|array',
                'inventories.*.combos.*.id' => 'required|integer',
                'inventories.*.combos.*.item_per_box' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $inventoriesData = $request->input('inventories');

            // Verify that all inventories belong to the same insert_key
            $allInventories = Inventory::where('insert_key', $insert_key)->get();
            if ($allInventories->isEmpty()) {
                throw new \Exception("No inventories found for insert_key: {$insert_key}");
            }

            foreach ($inventoriesData as $inventoryData) {
                // Update inventory record
                $inventory = Inventory::find($inventoryData['id']);
                if (!$inventory) {
                    throw new \Exception("Inventory not found: {$inventoryData['id']}");
                }

                // Verify this inventory belongs to the correct insert_key
                if ($inventory->insert_key !== $insert_key) {
                    throw new \Exception("Inventory {$inventoryData['id']} does not belong to insert_key {$insert_key}");
                }

                $inventory->update([
                    'sticker_meas_unit' => $inventoryData['sticker_meas_unit'],
                    'no_of_boxes' => $inventoryData['no_of_boxes'],
                    'net_wt' => $inventoryData['net_wt'],
                    'gross_wt' => $inventoryData['gross_wt'],
                    'carton_meas' => $inventoryData['carton_meas'],
                ]);

                // Update inventory combos
                foreach ($inventoryData['combos'] as $comboData) {
                    $combo = InventoryCombo::find($comboData['id']);
                    if (!$combo) {
                        throw new \Exception("Inventory combo not found: {$comboData['id']}");
                    }

                    $combo->update([
                        'item_per_box' => $comboData['item_per_box'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating inventory: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function invoiceList($id, Request $request)
    {

        $sort = NULL;
        if ( $request->sort == 'product_count' ) {
            $sort = 'asc';
            $request->request->remove('sort');
        } else if ( $request->sort == '-product_count' ) {
            $sort = 'desc';
            $request->request->remove('sort');
        }
        $inventories = $this->inventoryRepository->with(['combo',
                                                         'combo.product',
                                                         'combo.product.productAbstract'])->where('insert_key', $id)->when($sort,
            function ($q) use ($sort) {
                $q->orderBy('id', $sort);
            })->get();

        InventoryResource::withoutWrapping();
        InventoryResource::usingWithCollection();

        return new InventoryCollection($inventories);
    }

    public function downloadInventory(Request $request)
    {
        set_time_limit(300);

        $inventory = Inventory::with(['combo',
                                      'combo.product',
                                      'combo.product.productAbstract',
                                      'combo.product.productAbstract.brand'])
            ->where('insert_key', $request->id)->get();

//        return $inventory;

        $inventory->each(function ($inv) {
            $inv->combo->each(function ($combo) {
                if ( $combo->product && $combo->product->product_abstract ) {
                    $abstract = $combo->product->product_abstract;
                    if ( isset($abstract->image_url['imageUrls']) && is_array($abstract->image_url['imageUrls']) ) {
                        $abstract->image_url['imageUrls'] = array_map(function ($url) {
                            // Remove the base URL and convert to public path
                            $baseUrl = config('app.url');
                            $relativePath = str_replace($baseUrl, '', $url);
                            return public_path($relativePath);
                        }, $abstract->image_url['imageUrls']);
                    }
                }
            });
        });


     $customPaper = array(0, 0, 504, 360);
     // $pdf = PDF::loadView('pdf.retourlabel', compact('retour', 'barcode'))->setPaper($customPaper, 'portrait');

        $pdf = PDF::loadView('pdf.inventorypdfdownload', ['inventory' => $inventory])->setPaper($customPaper, 'portrait');
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'inventory-' . date('Y-m-d') . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function noPaginateProductList(Request $request)
    {
        if ( !Auth::user()->can("product.view") ) {
            return $this->sendError('Permission Denied');
        }
        $perPage  = getPageSize($request);
        $products = $this->productRepository;


        if ( $request->get('product_unit') ) {
            $products->where('product_unit', $request->get('product_unit'));
        }

        if ( $request->get('warehouse_id') && $request->get('warehouse_id') != 'null' ) {
            $warehouseId = $request->get('warehouse_id');
            $products->whereHas('stock', function ($q) use ($warehouseId) {
                $q->where('manage_stocks.warehouse_id', $warehouseId);
            })->with([
                'stock' => function (HasOne $query) use ($warehouseId) {
                    $query->where('manage_stocks.warehouse_id', $warehouseId);
                },
            ]);
        }

        // Filter by product name
        if ($request->get('filter')) {
            $filter = $request->get('filter');
            $products->where(function ($query) use ($filter) {
                $query->where('name', 'like', '%' . $filter . '%')
                    ->orWhereHas('productAbstract', function ($query) use ($filter) {
                        $query->where('name', 'like', '%' . $filter . '%')
                            ->orWhere('pan_style', 'like', '%' . $filter . '%');
                    });
            });
        }



        $products = $products->orderBy('id','desc')->get();
        ProductResource::usingWithCollection();

        return new ProductCollection($products);
    }

        public function delete($id) {
        $inventory = Inventory::findOrFail($id);
        $inventory->combo()->delete();
        $inventory->delete();

        return $this->sendSuccess('Brand deleted successfully');
    }
}
