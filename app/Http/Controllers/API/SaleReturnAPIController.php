<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateSaleReturnRequest;
use App\Http\Requests\UpdateSaleReturnRequest;
use App\Http\Resources\SaleReturnCollection;
use App\Http\Resources\SaleReturnResource;
use App\Models\Customer;
use App\Models\ManageStock;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\SaleReturnRepository;
use App\Helpers\StockHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\Auth;
class SaleReturnAPIController extends AppBaseController
{
    /**
     * @var SaleReturnRepository
     */
    private $saleReturnRepository;

    /**
     * SaleReturnAPIController constructor.
     *
     * @param  SaleReturnRepository  $saleReturnRepository
     */
    public function __construct(SaleReturnRepository $saleReturnRepository)
    {
        $this->saleReturnRepository = $saleReturnRepository;
    }

    /**
     * @param  Request  $request
     * @return SaleReturnCollection
     */
    public function index(Request $request)
    {
      
        if (!Auth::user()->can("manage.sale.return")) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $search = $request->filter['search'] ?? '';
        $customer = (Customer::where('name', 'LIKE', "%$search%")->get()->count() != 0);
        $warehouse = (Warehouse::where('name', 'LIKE', "%$search%")->get()->count() != 0);
        $salesReturn = $this->saleReturnRepository;
        if ($customer || $warehouse) {
            $salesReturn->whereHas('customer', function (Builder $q) use ($search, $customer) {
                if ($customer) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            })->whereHas('warehouse', function (Builder $q) use ($search, $warehouse) {
                if ($warehouse) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            });
        }

        if ($request->get('start_date') && $request->get('end_date')) {
            $salesReturn->whereBetween('date', [$request->get('start_date'), $request->get('end_date')]);
        }

        if ($request->get('warehouse_id')) {
            $salesReturn->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->get('customer_id')) {
            $salesReturn->where('customer_id', $request->get('customer_id'));
        }

        if ($request->get('status') && $request->get('status') != 'null') {
            $salesReturn->Where('status', $request->get('status'));
        }

        if ($request->get('payment_status') && $request->get('payment_status') != 'null') {
            $salesReturn->where('payment_status', $request->get('payment_status'));
        }

        $salesReturn = $salesReturn->paginate($perPage);

        SaleReturnResource::usingWithCollection();

        return new SaleReturnCollection($salesReturn);
    }

    /**
     * @param  CreateSaleReturnRequest  $request
     * @return SaleReturnResource
     */
    public function store(CreateSaleReturnRequest $request)
    {
        if (!Auth::user()->can('manage.sale.return')) {
            return $this->sendError('Permission Denied');
        }

        $input = $request->all();
        $saleReturn = $this->saleReturnRepository->storeSaleReturn($input);

        return new SaleReturnResource($saleReturn);
    }

    /**
     * @param $id
     * @return SaleReturnResource
     */
    public function show($id)
    {
        if (!Auth::user()->can('return.view')) {
            return $this->sendError('Permission Denied');
        }
        $saleReturn = $this->saleReturnRepository->find($id);

        return new SaleReturnResource($saleReturn);
    }

    /**
     * @param  SaleReturn  $salesReturn
     * @return SaleReturnResource
     */
    public function edit(SaleReturn $salesReturn)
    {
        if (!Auth::user()->can('return.edit')) {
            return $this->sendError('Permission Denied');
        }
        $salesReturn = $salesReturn->load(['saleReturnItems.product', 'warehouse','saleReturnItems.product' => function ($query) {
          
            $query->with(['variant','productAbstract:id,pan_style',]);
        }]);

        return new SaleReturnResource($salesReturn);
    }

    public function editBySale($saleId)
    {
        if (!Auth::user()->can('return.edit')) {
            return $this->sendError('Permission Denied');
        }
        $salesReturn = SaleReturn::where('sale_id', $saleId)->first();
        if (empty($salesReturn)) {
            return $this->sendError('Sale Return is not created');
        }
        $salesReturn = $salesReturn->load(['saleReturnItems', 'saleReturnItems.product', 'warehouse','saleReturnItems.product' => function ($query) {
          
            $query->with(['variant','productAbstract:id,pan_style',]);
        }]);

        return new SaleReturnResource($salesReturn);
    }

    /**
     * @param  UpdateSaleReturnRequest  $request
     * @param $id
     * @return SaleReturnResource
     */
    public function update(UpdateSaleReturnRequest $request, $id)
    {
        if (!Auth::user()->can('return.edit')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $saleReturn = $this->saleReturnRepository->updateSaleReturn($input, $id);

        return new SaleReturnResource($saleReturn);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('return.delete')) {
            return $this->sendError('Permission Denied');
        }
        try {
            DB::beginTransaction();
            $saleReturn = $this->saleReturnRepository->with('saleReturnItems')->where('id', $id)->first();
            $sale = Sale::whereId($saleReturn->sale_id)->first();
            if ($sale) {
                $sale->update(['is_return' => 0]);
            }
            foreach ($saleReturn->saleReturnItems as $saleReturnItem) {
                $product = ManageStock::whereWarehouseId($saleReturn->warehouse_id)->whereProductId($saleReturnItem['product_id'])->first();
                if ($product) {
                    if ($product->quantity >= $saleReturnItem['quantity']) {
                        $totalQuantity = $product->quantity - $saleReturnItem['quantity'];
                        $product->update([
                            'quantity' => $totalQuantity,
                        ]);
                    }
                }
            }
            $this->saleReturnRepository->delete($id);
            DB::commit();

            return $this->sendSuccess('Sale Return Deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param  SaleReturn  $salesReturn
     * @return \Illuminate\Http\JsonResponse
     */
    public function saleReturnInfo(SaleReturn $salesReturn)
    {
        $salesReturn = $salesReturn->load(['saleReturnItems.product', 'warehouse', 'customer','saleReturnItems.product' => function ($query) {
          
            $query->with(['variant','productAbstract:id,pan_style',]);
        }]);
        $keyName = [
            'email', 'company_name', 'phone', 'address',
        ];
        $salesReturn['company_info'] = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();

        return $this->sendResponse($salesReturn, 'Sale Return information retrieved successfully');
    }

    /**
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function pdfDownload(SaleReturn $saleReturn): JsonResponse
    {
        $saleReturn = $saleReturn->load('customer', 'saleReturnItems.product');
        $data = [];
        if (Storage::exists('pdf/sale_return-'.$saleReturn->reference_code.'.pdf')) {
            Storage::delete('pdf/sale_return-'.$saleReturn->reference_code.'.pdf');
        }
        $companyLogo = getLogoUrl();
        $pdf = PDF::loadView('pdf.sale-return-pdf', compact('saleReturn', 'companyLogo'))->setOptions([
            'tempDir' => public_path(),
            'chroot' => public_path(),
        ]);
        Storage::disk(config('app.media_disc'))->put('pdf/sale_return-'.$saleReturn->reference_code.'.pdf',
            $pdf->output());
        $data['sale_return_pdf_url'] = Storage::url('pdf/sale_return-'.$saleReturn->reference_code.'.pdf');

        return $this->sendResponse($data, 'Sale return pdf retrieved Successfully');
    }

    /**
     * @param  Request  $request
     * @return SaleReturnCollection
     */
    public function getSaleReturnProductReport(Request $request): SaleReturnCollection
    {
        $perPage = getPageSize($request);
        $productId = $request->get('product_id');
        $saleReturns = $this->saleReturnRepository->whereHas('saleReturnItems', function ($q) use ($productId) {
            $q->where('product_id', '=', $productId);
        })->with(['saleReturnItems.product', 'customer']);

        $saleReturns = $saleReturns->paginate($perPage);

        SaleReturnResource::usingWithCollection();

        return new SaleReturnCollection($saleReturns);
    }

    /**
     * Approve a sales return and update stock
     *
     * @param int $id
     * @return JsonResponse
     */
    public function approve($id): JsonResponse
    {
        if (!Auth::user()->can('return.edit')) {
            return $this->sendError('Permission Denied');
        }

        try {
            DB::beginTransaction();

            $saleReturn = $this->saleReturnRepository->find($id);

            if (!$saleReturn) {
                return $this->sendError('Sales return not found', 404);
            }

            if ($saleReturn->isApproved()) {
                return $this->sendError('Sales return is already approved', 400);
            }

            if (!$saleReturn->isPending()) {
                return $this->sendError('Only pending returns can be approved', 400);
            }

            // Approve the return
            $saleReturn->approve(Auth::id());

            // Update stock for each return item
            foreach ($saleReturn->saleReturnItems as $returnItem) {
                $product = $returnItem->product;
                $quantity = $returnItem->quantity;

                // Update stock in MySQL (manage_stocks table)
                $manageStock = ManageStock::where('warehouse_id', $saleReturn->warehouse_id)
                    ->where('product_id', $product->id)
                    ->first();

                if ($manageStock) {
                    $manageStock->increment('quantity', $quantity);
                } else {
                    // Create new stock record if it doesn't exist
                    ManageStock::create([
                        'warehouse_id' => $saleReturn->warehouse_id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                    ]);
                }

                // Update stock in PostgreSQL using StockHelper
                StockHelper::manageStockForCodeAndWarehouse($product->code, $saleReturn->warehouse_id);
            }

            // Mark stock as updated
            $saleReturn->markStockUpdated();

            DB::commit();

            return $this->sendResponse(
                new SaleReturnResource($saleReturn),
                'Sales return approved and stock updated successfully'
            );

        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to approve sales return: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Approve specific items in a sales return and update stock
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approvePartial(Request $request, $id): JsonResponse
    {
        if (!Auth::user()->can('return.edit')) {
            return $this->sendError('Permission Denied');
        }

        $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:sale_return_items,id'
        ]);

        try {
            DB::beginTransaction();

            $saleReturn = $this->saleReturnRepository->find($id);

            if (!$saleReturn) {
                return $this->sendError('Sales return not found', 404);
            }

            $itemIds = $request->input('item_ids');

            // Get the specific items to approve
            $itemsToApprove = $saleReturn->saleReturnItems()->whereIn('id', $itemIds)->get();

            if ($itemsToApprove->isEmpty()) {
                return $this->sendError('No valid items found to approve', 400);
            }

            // Update stock for each approved item
            foreach ($itemsToApprove as $returnItem) {
                $product = $returnItem->product;
                $quantity = $returnItem->quantity;

                // Update stock in MySQL (manage_stocks table)
                $manageStock = ManageStock::where('warehouse_id', $saleReturn->warehouse_id)
                    ->where('product_id', $product->id)
                    ->first();

                if ($manageStock) {
                    $manageStock->increment('quantity', $quantity);
                } else {
                    // Create new stock record if it doesn't exist
                    ManageStock::create([
                        'warehouse_id' => $saleReturn->warehouse_id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                    ]);
                }

                // Update stock in PostgreSQL using StockHelper
                StockHelper::manageStockForCodeAndWarehouse($product->code, $saleReturn->warehouse_id);

                // Mark this specific item as approved
                $returnItem->update([
                    'is_approved' => true,
                    'approved_at' => now(),
                    'approved_by' => Auth::id()
                ]);
            }

            // Check if all items are now approved
            $totalItems = $saleReturn->saleReturnItems()->count();
            $approvedItems = $saleReturn->saleReturnItems()->where('is_approved', true)->count();

            if ($totalItems === $approvedItems) {
                // All items approved, mark the entire return as approved
                $saleReturn->approve(Auth::id());
                $saleReturn->markStockUpdated();
                $message = 'All items approved. Sales return fully approved and stock updated successfully';
            } else {
                // Partial approval
                $saleReturn->update([
                    'return_status' => 'Partially Approved',
                    'stock_updated_at' => now()
                ]);
                $message = 'Selected items approved and stock updated successfully. Return is partially approved.';
            }

            DB::commit();

            return $this->sendResponse(
                new SaleReturnResource($saleReturn),
                $message
            );

        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to approve selected items: ' . $e->getMessage(), 500);
        }
    }
}
