<?php

namespace App\Repositories;

use App\Models\ManageStock;
use App\Models\Transfer;
use App\Models\TransferItem;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class TransferRepository
 */
class TransferRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'tax_rate',
        'tax_amount',
        'discount',
        'shipping',
        'grand_total',
        'note',
        'created_at',
        'reference_code',
    ];

    /**
     * @var string[]
     */
    protected $allowedFields = [
        'date',
        'tax_rate',
        'tax_amount',
        'discount',
        'shipping',
        'grand_total',
        'note',
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Transfer::class;
    }

    /**
     * @param $input
     * @return mixed
     */
    public function storeTransfer($input)
    {
        try {
            DB::beginTransaction();

            $input['date'] = $input['date'] ?? date('Y/m/d');
            $TransferInputArray = Arr::only($input, [
                'from_warehouse_id', 'to_warehouse_id', 'tax_rate', 'tax_amount', 'discount', 'shipping', 'grand_total',
                'note', 'date', 'status',
            ]);

            /** @var Transfer $transfer */
            $transfer = Transfer::create($TransferInputArray);
            $transfer = $this->storeTransferItems($transfer, $input);

            DB::commit();

            return $transfer;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $transfer
     * @param $input
     * @return mixed
     */
    public function storeTransferItems($transfer, $input)
    {
        foreach ($input['transfer_items'] as $transferItem) {
            $product = ManageStock::whereWarehouseId($input['from_warehouse_id'])->whereProductId($transferItem['product_id'])->first();

            if ($product) {
                if ($transferItem['quantity'] > $product->quantity) {
                    throw new UnprocessableEntityHttpException('Quantity should not be greater than available quantity.');
                } else {
                    /** @var \App\Services\StockService $stockService */
                    $stockService = app(\App\Services\StockService::class);
                    
                    // Add to destination
                    $stockService->updateStock(
                        $input['to_warehouse_id'],
                        $transferItem['product_id'],
                        $transferItem['quantity'],
                        Transfer::class,
                        $transfer->id,
                        'transfer_in',
                        'Transfer In'
                    );

                    // Subtract from source
                    $stockService->updateStock(
                        $input['from_warehouse_id'],
                        $transferItem['product_id'],
                        -1 * $transferItem['quantity'],
                        Transfer::class,
                        $transfer->id,
                        'transfer_out',
                        'Transfer Out'
                    );
                }
            } else {
                throw new UnprocessableEntityHttpException('Product stock is not available in selected warehouse.');
            }

            $item = $this->calculationTransferItems($transferItem);
            $transferItem = new TransferItem($item);
            $transfer->transferItems()->save($transferItem);
        }

        $subTotalAmount = $transfer->transferItems()->sum('sub_total');

        if ($input['discount'] <= $subTotalAmount) {
            $input['grand_total'] = $subTotalAmount - $input['discount'];
        } else {
            throw new UnprocessableEntityHttpException('Discount amount should not be greater than total.');
        }
        if ($input['tax_rate'] <= 100 && $input['tax_rate'] >= 0) {
            $input['tax_amount'] = $input['grand_total'] * $input['tax_rate'] / 100;
        } else {
            throw new UnprocessableEntityHttpException('Please enter tax value between 0 to 100.');
        }
        $input['grand_total'] += $input['tax_amount'];
        if ($input['shipping'] <= $input['grand_total'] && $input['shipping'] >= 0) {
            $input['grand_total'] += $input['shipping'];
        } else {
            throw new UnprocessableEntityHttpException('Shipping amount should not be greater than total.');
        }

        $input['reference_code'] = 'TR_111'.$transfer->id;
        $transfer->update($input);

        return $transfer;
    }

    /**
     * @param $transferItem
     * @return mixed
     */
    public function calculationTransferItems($transferItem)
    {
        $validator = Validator::make($transferItem, TransferItem::$rules);
        if ($validator->fails()) {
            throw new UnprocessableEntityHttpException($validator->errors()->first());
        }

        //discount calculation
        $perItemDiscountAmount = 0;
        $transferItem['net_unit_price'] = $transferItem['product_price'];
        if ($transferItem['discount_type'] == Transfer::PERCENTAGE) {
            if ($transferItem['discount_value'] <= 100 && $transferItem['discount_value'] >= 0) {
                $transferItem['discount_amount'] = ($transferItem['discount_value'] * $transferItem['product_price'] / 100) * $transferItem['quantity'];
                $perItemDiscountAmount = $transferItem['discount_amount'] / $transferItem['quantity'];
                $transferItem['net_unit_price'] -= $perItemDiscountAmount;
            } else {
                throw new UnprocessableEntityHttpException('Please enter discount value between 0 to 100.');
            }
        } elseif ($transferItem['discount_type'] == Transfer::FIXED) {
            if ($transferItem['discount_value'] <= $transferItem['product_price'] && $transferItem['discount_value'] >= 0) {
                $transferItem['discount_amount'] = $transferItem['discount_value'] * $transferItem['quantity'];
                $perItemDiscountAmount = $transferItem['discount_amount'] / $transferItem['quantity'];
                $transferItem['net_unit_price'] -= $perItemDiscountAmount;
            } else {
                throw new UnprocessableEntityHttpException("Please enter  discount's value between product's price.");
            }
        }

        //tax calculation
        $perItemTaxAmount = 0;
        if ($transferItem['tax_value'] <= 100 && $transferItem['tax_value'] >= 0) {
            if ($transferItem['tax_type'] == Transfer::EXCLUSIVE) {
                $transferItem['tax_amount'] = (($transferItem['net_unit_price'] * $transferItem['tax_value']) / 100) * $transferItem['quantity'];
                $perItemTaxAmount = $transferItem['tax_amount'] / $transferItem['quantity'];
            } elseif ($transferItem['tax_type'] == Transfer::INCLUSIVE) {
                $transferItem['tax_amount'] = ($transferItem['net_unit_price'] * $transferItem['tax_value']) / (100 + $transferItem['tax_value']) * $transferItem['quantity'];
                $perItemTaxAmount = $transferItem['tax_amount'] / $transferItem['quantity'];
                $transferItem['net_unit_price'] -= $perItemTaxAmount;
            }
        } else {
            throw new UnprocessableEntityHttpException('Please enter tax value between 0 to 100 ');
        }
        $transferItem['sub_total'] = ($transferItem['net_unit_price'] + $perItemTaxAmount) * $transferItem['quantity'];

        return $transferItem;
    }

    /**
     * @param $input
     * @param $id
     * @return mixed
     */
    public function updateTransfer($input, $id)
    {
        try {
            DB::beginTransaction();

            $transfer = Transfer::findOrFail($id);

            $transferItemOldIds = TransferItem::whereTransferId($id)->pluck('id')->toArray();
            $transferItemNewIds = [];

            foreach ($input['transfer_items'] as $key => $transferItem) {
                $transferItemNewIds[$key] = $transferItem['transfer_item_id'];

                $transferItemArray = Arr::only($transferItem, [
                    'transfer_item_id', 'product_id', 'product_price', 'net_unit_price', 'tax_type', 'tax_value',
                    'tax_amount', 'discount_type', 'discount_value', 'discount_amount', 'quantity',
                    'sub_total',
                ]);

                if (! is_null($transferItem['transfer_item_id'])) {
                    $this->updateItem($transferItemArray, $transfer->from_warehouse_id, $transfer->to_warehouse_id);
                }

                if (is_null($transferItem['transfer_item_id'])) {
                    $product = ManageStock::whereWarehouseId($transfer->from_warehouse_id)->whereProductId($transferItem['product_id'])->first();

                    if ($product) {
                        if ($transferItem['quantity'] > $product->quantity) {
                            throw new UnprocessableEntityHttpException('Quantity should not be greater than available quantity.');
                        } else {
                             /** @var \App\Services\StockService $stockService */
                             $stockService = app(\App\Services\StockService::class);
                             
                             // Add to destination
                             $stockService->updateStock(
                                 $transfer->to_warehouse_id,
                                 $transferItem['product_id'],
                                 $transferItem['quantity'],
                                 Transfer::class,
                                 $transfer->id,
                                 'transfer_in_update',
                                 'Transfer In (Updated)'
                             );

                             // Subtract from source
                             $stockService->updateStock(
                                 $transfer->from_warehouse_id, 
                                 $transferItem['product_id'],
                                 -1 * $transferItem['quantity'],
                                 Transfer::class,
                                 $transfer->id,
                                 'transfer_out_update',
                                 'Transfer Out (Updated)'
                             );
                        }
                    } else {
                        throw new UnprocessableEntityHttpException('Product stock is not available in selected warehouse.');
                    }

                    $item = $this->calculationTransferItems($transferItem);
                    $transferItem = new TransferItem($item);
                    $transfer->transferItems()->save($transferItem);
                }
            }

            $removeItemIds = array_diff($transferItemOldIds, $transferItemNewIds);

            if (! empty(array_values($removeItemIds))) {
                foreach ($removeItemIds as $removeItemId) {
                    $oldTransferItem = TransferItem::whereId($removeItemId)->first();
                    $oldTransfer = Transfer::whereId($oldTransferItem->transfer_id)->first();
                    /** @var \App\Services\StockService $stockService */
                    $stockService = app(\App\Services\StockService::class);
                    
                    // Removing transfer item:
                    // Source: Add back quantity (Reverse logic of creation)
                    // Destination: Subtract quantity (Reverse logic of creation)
                    
                    // Destination Revert
                    $stockService->updateStock(
                        $oldTransfer->to_warehouse_id,
                        $oldTransferItem->product_id,
                        -1 * $oldTransferItem->quantity,
                        Transfer::class,
                        $oldTransfer->id,
                        'transfer_in_remove',
                        'Transfer In Removed'
                    );

                    // Source Revert
                    $stockService->updateStock(
                        $oldTransfer->from_warehouse_id,
                        $oldTransferItem->product_id,
                        $oldTransferItem->quantity,
                        Transfer::class,
                        $oldTransfer->id,
                        'transfer_out_remove',
                        'Transfer Out Removed'
                    );
                }

                TransferItem::whereIn('id', array_values($removeItemIds))->delete();
            }

            $transfer = $this->updateTransferCalculation($input, $id);

            DB::commit();

            return $transfer;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $transferItem
     * @param $fromWarehouseId
     * @param $toWarehouseId
     * @return bool
     */
    public function updateItem($transferItem, $fromWarehouseId, $toWarehouseId): bool
    {
        try {
            $transferItem = $this->calculationTransferItems($transferItem);

            $item = TransferItem::whereId($transferItem['transfer_item_id'])->first();
            $transfer = Transfer::whereId($item->transfer_id)->first();

            $fromWarehouseId = $transfer->from_warehouse_id;
            $toWarehouseId = $transfer->to_warehouse_id;

            $fromQuantity = 0;

            if ($item->quantity >= $transferItem['quantity']) {
                $fromQuantityDiff = $item->quantity - $transferItem['quantity'];
                $fromQuantity = $fromQuantity + $fromQuantityDiff;
            } else {
                $fromQuantityDiff = $transferItem['quantity'] - $item->quantity;
                $fromQuantity = $fromQuantity - $fromQuantityDiff;
            }

            /** @var \App\Services\StockService $stockService */
            $stockService = app(\App\Services\StockService::class);

            if ($fromQuantityDiff != 0) {
                // If diff is positive, it means we consumed LESS from source?
                // Logic above:
                // if item->qty >= new->qty (Reduced transfer size):
                // diff = old - new (positive)
                // fromQty = fromQty + diff (Add back to source)
                // else (Increased transfer size):
                // diff = new - old (positive)
                // fromQty = fromQty - diff (Subtract more from source)
                
                // My variable naming and logic in original code was accumulation in $fromQuantity variable but inconsistent.
                // Let's rely on $fromQuantity calculated above.
                // If $fromQuantity > 0, it means we are adding back to source.
                // If $fromQuantity < 0, it means we are subtracting from source.
                
                // However, I need to check sufficiency if subtracting.
                if ($fromQuantity < 0) {
                     $product = ManageStock::whereWarehouseId($fromWarehouseId)->whereProductId($transferItem['product_id'])->first();
                     if (!$product || ($product->quantity + $fromQuantity) < 0) { // $fromQuantity is neg
                          throw new UnprocessableEntityHttpException('Quantity should not be greater than available quantity.');
                     }
                }
                
                $stockService->updateStock(
                    $fromWarehouseId,
                    $item->product_id,
                    $fromQuantity, // calculated diff
                    Transfer::class,
                    $transfer->id,
                    'transfer_out_update',
                    'Transfer Out Updated'
                );
            }

            // Target Warehouse Logic
            // $toQuantity calculated above.
            // if item->qty >= new->qty (Reduced transfer size):
            // diff = old - new
            // toQty = toQty - diff (Remove from target)
            // else (Increased transfer size):
            // diff = new - old
            // toQty = toQty + diff (Add to target)
            
            if ($toQuantityDiff != 0) {
                 $stockService->updateStock(
                    $toWarehouseId,
                    $item->product_id,
                    $toQuantity,
                    Transfer::class,
                    $transfer->id,
                    'transfer_in_update',
                    'Transfer In Updated'
                );
            }

            unset($transferItem['transfer_item_id']);

            $item->update($transferItem);

            return true;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function updateTransferCalculation($input, $id)
    {
        $transfer = Transfer::findOrFail($id);
        $subTotalAmount = $transfer->transferItems()->sum('sub_total');

        if ($input['discount'] > $subTotalAmount || $input['discount'] < 0) {
            throw new UnprocessableEntityHttpException('Discount amount should not be greater than total.');
        }
        $input['grand_total'] = $subTotalAmount - $input['discount'];
        if ($input['tax_rate'] > 100 || $input['tax_rate'] < 0) {
            throw new UnprocessableEntityHttpException('Please enter tax value between 0 to 100.');
        }
        $input['tax_amount'] = $input['grand_total'] * $input['tax_rate'] / 100;

        $input['grand_total'] += $input['tax_amount'];

        if ($input['shipping'] > $input['grand_total'] || $input['shipping'] < 0) {
            throw new UnprocessableEntityHttpException('Shipping amount should not be greater than total.');
        }

        $input['grand_total'] += $input['shipping'];

        $transferInputArray = Arr::only($input, [
            'from_warehouse_id', 'to_warehouse_id', 'tax_rate', 'tax_amount', 'discount', 'shipping', 'grand_total',
            'note', 'date', 'status',
        ]);
        $transfer->update($transferInputArray);

        return $transfer;
    }
}
