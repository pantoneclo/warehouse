<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreatePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseCollection;
use App\Http\Resources\PurchaseResource;
use App\Models\ManageStock;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Repositories\PurchaseRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
/**
 * Class PurchaseAPIController
 */
class PurchaseAPIController extends AppBaseController
{
    /** @var PurchaseRepository */
    private $purchaseRepository;
    private  $stockManagementController;
    public function __construct(PurchaseRepository $purchaseRepository, StockManagementAPIController $stockManagementController)
    {
        $this->purchaseRepository = $purchaseRepository;
        $this->stockManagementController = $stockManagementController;
    }

    /**
     * @param  Request  $request
     * @return PurchaseCollection
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can("manage.purchase")) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $search = $request->filter['search'] ?? '';
        $supplier = (Supplier::where('name', 'LIKE', "%$search%")->get()->count() != 0);
        $warehouse = (Warehouse::where('name', 'LIKE', "%$search%")->get()->count() != 0);

        $purchases = $this->purchaseRepository;
        if ($supplier || $warehouse) {
            $purchases->whereHas('supplier', function (Builder $q) use ($search, $supplier) {
                if ($supplier) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            })->whereHas('warehouse', function (Builder $q) use ($search, $warehouse) {
                if ($warehouse) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            });
        }


            $purchases->whereHas('purchaseItems.product.productAbstract', function ($q) use ($search) {
                $q->where('pan_style', 'LIKE', "%$search%");
            });


        if ($request->get('start_date') && $request->get('end_date')) {
            $purchases->whereBetween('date', [$request->get('start_date'), $request->get('end_date')]);
        }

        if ($request->get('warehouse_id')) {
            $purchases->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->get('status')) {
            $purchases->where('status', $request->get('status'));
        }

        $purchases = $purchases->paginate($perPage);

        PurchaseResource::usingWithCollection();

        return new PurchaseCollection($purchases);
    }

    /**
     * @param  CreatePurchaseRequest  $request
     * @return PurchaseResource
     */
    public function store(CreatePurchaseRequest $request)
    {
        if (!Auth::user()->can('purchase.create')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $purchase = $this->purchaseRepository->storePurchase($input);


        // Use PurchaseResource to format the incoming data


        try {
            // Check if the necessary fields exist in the formatted purchase data
            if (isset($purchase['warehouse_id'])) {
                // Extract the necessary fields from the formatted data
                $warehouse_id = $purchase['warehouse_id'];
                $warehouse = Warehouse::whereId($warehouse_id)->first();
                $warehouse_code = $warehouse->country_code;
                $operation = 'inventory'; // Define the operation type
                $saleItems = $purchase->purchaseItems->toArray();
                if (empty($saleItems)) {
                    dd("Sale items are empty or not an array", $saleItems);
                }

                foreach ($saleItems as $saleItem) {

                    manageStock( $warehouse_id, $saleItem['product_id'], 0);

                }
                
            } else {
                // If required data is missing, debug or log the issue
                dd($purchase);
            }
        } catch (\Exception $e) {
            // Handle any errors that occur during the stock management process
            dd('Error during stock management: ' . $e->getMessage());
        }

        // Optionally return the formatted purchase data
        return new PurchaseResource($purchase);
    }

    /**
     * @param $id
     * @return PurchaseResource
     */
    public function show($id)
    {
        if (!Auth::user()->can('purchase.view')) {
            return $this->sendError('Permission Denied');
        }
        $purchase = $this->purchaseRepository->find($id);


        return new PurchaseResource($purchase);
    }

    /**
     * @param  Purchase  $purchase
     * @return PurchaseResource
     */
    public function edit(Purchase $purchase)
    {
        if (!Auth::user()->can('purchase.edit')) {
            return $this->sendError('Permission Denied');
        }
        $purchase = $purchase->load(['purchaseItems.product.stocks',
        'warehouse','purchaseItems.product' => function ($query) {

            $query->with(['variant','productAbstract:id,pan_style',]);
        },]);

        return new PurchaseResource($purchase);
    }

    /**
     * @param  UpdatePurchaseRequest  $request
     * @param $id
     * @return PurchaseResource
     */
    public function update(UpdatePurchaseRequest $request, $id): PurchaseResource
    {
        if (!Auth::user()->can('purchase.edit')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $purchase = $this->purchaseRepository->updatePurchase($input, $id);

        try {
            // Check if the necessary fields exist in the formatted purchase data
            if (isset($purchase['warehouse_id'])) {
                // Extract the necessary fields from the formatted data
                $warehouse_id = $purchase['warehouse_id'];
                $warehouse = Warehouse::whereId($warehouse_id)->first();
                $warehouse_code = $warehouse->country_code;
                $operation = 'inventory'; // Define the operation type
                $saleItems = $purchase->purchaseItems->toArray();
                if (empty($saleItems)) {
                    dd("Sale items are empty or not an array", $saleItems);
                }
                // Call stock management controller to handle inventory
                $this->stockManagementController->prepareStockItems($warehouse_id, $warehouse_code, $saleItems, $operation);
            } else {
                // If required data is missing, debug or log the issue
                dd($purchase);
            }
        } catch (\Exception $e) {
            // Handle any errors that occur during the stock management process
            dd('Error during stock management: ' . $e->getMessage());
        }

        return new PurchaseResource($purchase);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('purchase.delete')) {
            return $this->sendError('Permission Denied');
        }
        try {
            DB::beginTransaction();
            //manage stock
            $purchase = $this->purchaseRepository->with('purchaseItems')->where('id', $id)->first();
            foreach ($purchase->purchaseItems as $purchaseItem) {
                $product = ManageStock::whereWarehouseId($purchase->warehouse_id)
                    ->whereProductId($purchaseItem['product_id'])
                    ->first();
                if ($product) {
                    if ($product->quantity >= $purchaseItem['quantity']) {
                        $totalQuantity = $product->quantity - $purchaseItem['quantity'];
                        $product->update([
                            'quantity' => $totalQuantity,
                        ]);
                    } else {
                        throw new UnprocessableEntityHttpException('Quantity must be less than Available quantity.');
                    }
                }
            }
            $this->purchaseRepository->delete($id);
            DB::commit();

            return $this->sendSuccess('Purchase Deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }


    // public function destroy($id)
    // {
    //     if (!Auth::user()->can('purchase.delete')) {
    //         return $this->sendError('Permission Denied');
    //     }
    //     try {
    //         DB::beginTransaction();

    //     // Get all purchases
    //     $purchases = $this->purchaseRepository->with('purchaseItems')->get();

    //     foreach ($purchases as $purchase) {
    //         foreach ($purchase->purchaseItems as $purchaseItem) {
    //             $product = ManageStock::whereWarehouseId($purchase->warehouse_id)
    //                 ->whereProductId($purchaseItem['product_id'])
    //                 ->first();
    //             if ($product) {

    //                     $product->update([
    //                         'quantity' => 0,
    //                     ]);

    //             }
    //         }
    //         // Delete the individual purchase
    //         $this->purchaseRepository->delete($purchase->id);
    //     }

    //     DB::commit();
    //     return $this->sendSuccess('All Purchases Deleted successfully');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         throw new UnprocessableEntityHttpException($e->getMessage());
    //     }
    // }

    /**
     * @param  Purchase  $purchase
     * @return JsonResponse
     *
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function pdfDownload(Purchase $purchase): JsonResponse
    {
        $purchase = $purchase->load('purchaseItems.product', 'supplier');

        $data = [];
        if (Storage::exists('pdf.purchase-pdf-'.$purchase->reference_code.'.pdf')) {
            Storage::delete('pdf.purchase-pdf-'.$purchase->reference_code.'.pdf');
        }

        $pdf = PDF::loadView('pdf.purchase-pdf', compact('purchase'))->setOptions([
            'tempDir' => public_path(),
            'chroot' => public_path(),
        ]);
        Storage::disk(config('app.media_disc'))->put('pdf/Purchase-'.$purchase->reference_code.'.pdf', $pdf->output());
        $data['purchase_pdf_url'] = Storage::url('pdf/Purchase-'.$purchase->reference_code.'.pdf');

        return $this->sendResponse($data, 'pdf retrieved Successfully');
    }

    /**
     * @param  Purchase  $purchase
     * @return JsonResponse
     */
    public function purchaseInfo(Purchase $purchase)
    {
        $purchase = $purchase->load(['purchaseItems.product' => function ($query) {

            $query->with(['variant','productAbstract:id,pan_style',]);
        },

        'warehouse', 'supplier']);


        $keyName = [
            'email', 'company_name', 'phone', 'address',
        ];
        $purchase['company_info'] = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();

        return $this->sendResponse($purchase, 'Purchase information retrieved successfully');
    }

    /**
     * @param  Request  $request
     * @return PurchaseCollection
     */
    public function getPurchaseProductReport(Request $request): PurchaseCollection
    {
        $perPage = getPageSize($request);
        $productId = $request->get('product_id');
        $purchases = $this->purchaseRepository->whereHas('purchaseItems', function ($q) use ($productId) {
            $q->where('product_id', '=', $productId);
        })->with(['purchaseItems.product', 'supplier']);

        $purchases = $purchases->paginate($perPage);

        PurchaseResource::usingWithCollection();

        return new PurchaseCollection($purchases);
    }
}
