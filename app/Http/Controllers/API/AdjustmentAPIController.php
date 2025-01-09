<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CraeteAdjustmentRequest;
use App\Http\Requests\UpdateAdjustmentRequest;
use App\Http\Resources\AdjustmentCollection;
use App\Http\Resources\AdjustmentResource;
use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\ManageStock;
use App\Repositories\AdjustmentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

use App\Http\Controllers\API\StockManagementAPIController;
use App\Jobs\ProcessAdjustmentItems;
class AdjustmentAPIController extends AppBaseController
{
    /** @var AdjustmentRepository */
    private $adjustmentRepository;

    public function __construct(AdjustmentRepository $adjustmentRepository, StockManagementAPIController $stockmanagement )
    {
        $this->adjustmentRepository = $adjustmentRepository;
        $this->stockmanagement = $stockmanagement;
    }

    /**
     * @param  Request  $request
     * @return AdjustmentCollection
     */
    public function index(Request $request)
    {

        if(!Auth::user()->can ('manage.adjustments')) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);

        $adjustments = $this->adjustmentRepository;

        if ($request->get('warehouse_id')) {
            $adjustments->where('warehouse_id', $request->get('warehouse_id'));
        }

        $adjustments = $adjustments->paginate($perPage);


        AdjustmentResource::usingWithCollection();

        return new AdjustmentCollection($adjustments);
    }

    /**
     * @param  Request  $request
     * @return AdjustmentResource
     */
    public function store(CraeteAdjustmentRequest $request)
    {
        if (!Auth::user()->can ('adjustment.create')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        // Store the adjustment
        $adjustment = $this->adjustmentRepository->storeAdjustment($input);

        // Dispatch the background job for processing adjustment items
        try {
            // Validate adjustment items
            if (!isset($input['adjustment_items']) || !is_array($input['adjustment_items'])) {
                return $this->sendError('Adjustment items are invalid or missing.');
            }

            // Dispatch the job to process the adjustment items in the background
            ProcessAdjustmentItems::dispatch($input['adjustment_items'], $input['warehouse_id']);

            // Return response immediately
            return new AdjustmentResource($adjustment);
        } catch (\Exception $e) {
            return $this->sendError('An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * @param  Adjustment  $adjustment
     * @return AdjustmentResource
     */
    public function show(Adjustment $adjustment)
    {
        if (!Auth::user()->can ('adjustment.view')) {
            return $this->sendError('Permission Denied');
        }

        $adjustment = $adjustment->load(['adjustmentItems.product','adjustmentItems.product' => function ($query) {

            $query->with(['variant','productAbstract:id,pan_style',]);
        }]);

        return new AdjustmentResource($adjustment);
    }

    /**
     * @param  Adjustment  $adjustment
     * @return AdjustmentResource
     */
    public function edit(Adjustment $adjustment)
    {if (!Auth::user()->can ('adjustment.edit')) {
            return $this->sendError('Permission Denied');
        }
        $adjustment = $adjustment->load(['adjustmentItems.product.stocks', 'warehouse','adjustmentItems.product' => function ($query) {

            $query->with(['variant','productAbstract:id,pan_style',]);
        }]);

        return new AdjustmentResource($adjustment);
    }

    /**
     * @param  UpdateAdjustmentRequest  $request
     * @param $id
     * @return AdjustmentResource
     */
    public function update(UpdateAdjustmentRequest $request, $id)
    {
        if (!Auth::user()->can ('adjustment.edit')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $adjustment = $this->adjustmentRepository->updateAdjustment($input, $id);

        return new AdjustmentResource($adjustment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->can ('adjustment.delete')) {
            return $this->sendError('Permission Denied');
        }
        try {
            DB::beginTransaction();

            $adjustment = $this->adjustmentRepository->with('adjustmentItems')->where('id', $id)->firstOrFail();

            foreach ($adjustment->adjustmentItems as $adjustmentItem) {
                $oldItem = AdjustmentItem::whereId($adjustmentItem->id)->firstOrFail();
                $existProductStock = ManageStock::whereWarehouseId($adjustment->warehouse_id)->whereProductId($oldItem->product_id)->first();

                if ($oldItem->method_type == AdjustmentItem::METHOD_ADDITION) {
                    $totalQuantity = $existProductStock->quantity - $oldItem['quantity'];
                } else {
                    $totalQuantity = $existProductStock->quantity + $oldItem['quantity'];
                }

                $existProductStock->update([
                    'quantity' => $totalQuantity,
                ]);
            }

            $this->adjustmentRepository->delete($id);

            DB::commit();

            return $this->sendSuccess('Adjustment delete successfully');
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
