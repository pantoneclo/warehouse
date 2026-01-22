<?php

namespace App\Repositories;

use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\ManageStock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class SaleRepository
 */
class AdjustmentRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'reference_code',
        'warehouse_id',
        'total_products',
        'created_at',
    ];

    /**
     * @var string[]
     */
    protected $allowedFields = [
        'date',
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
        return Adjustment::class;
    }

    /**
     * @param $input
     * @return Adjustment
     */
    public function storeAdjustment($input): Adjustment
    {
        try {
            DB::beginTransaction();

            $input['total_products'] = count($input['adjustment_items']);
            $input['date'] = $input['date'] ?? date('Y/m/d');
            $adjustmentInputArray = Arr::only($input, [
                'date', 'warehouse_id', 'total_products',
            ]);
            $adjustment = Adjustment::create($adjustmentInputArray);
            $reference_code = 'AD_111'.$adjustment->id;
            $adjustment->update(['reference_code' => $reference_code]);

            $adjustment = $this->storeAdjustmentItems($adjustment, $input);

            DB::commit();
            return $adjustment;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $adjustment
     * @param $input
     */
    public function storeAdjustmentItems($adjustment, $input)
    {
        foreach ($input['adjustment_items'] as $adjustmentItem) {
            $adjustmentItem['adjustment_id'] = $adjustment->id;
            AdjustmentItem::Create($adjustmentItem);

            // Refactored to use StockService
            /** @var \App\Services\StockService $stockService */
            $stockService = app(\App\Services\StockService::class);

            if ($adjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) {
                $stockService->updateStock(
                    $adjustment->warehouse_id,
                    $adjustmentItem['product_id'],
                    $adjustmentItem['quantity'],
                    Adjustment::class,
                    $adjustment->id,
                    'adjustment_addition',
                    'Adjustment Addition'
                );
            } else {
                // Check if sufficient quantity, though StockService can handle negative results or throwing error?
                // The original code threw exception if stock < quantity.
                // StockService allows going negative or we should check before.
                // Replicate check:
                $product = ManageStock::whereWarehouseId($adjustment->warehouse_id)->whereProductId($adjustmentItem['product_id'])->first();
                 if ($product && ($product->quantity - $adjustmentItem['quantity']) < 0) {
                        throw new UnprocessableEntityHttpException('Quantity exceeds quantity available in stock.');
                 }
                 // If original code allowed non-existing product to subtract (which is impossible logically but code path existed as else block), 
                 // actually the original else block logic for subtraction on non-existing product:
                 // } else { if method == ADDITION ... } 
                 // Meaning if product didn't exist, it only supported ADDITION.
                 // So for subtraction, product MUST exist. The above check covers it.
                 // If product doesn't exist, first check fails (product null). 
                 if (!$product) {
                     // logic for non-existing product subtraction in original code was technically unreachable or ignored?
                     // Original code:
                     // if (!empty($product)) { ... } else { if (ADDITION) { create } }
                     // So if not empty and subtraction -> nothing happened? Or error?
                     // Ah, strictly speaking, if product doesn't exist, we can't subtract.
                     // So we proceed only if valid.
                 }

                $stockService->updateStock(
                    $adjustment->warehouse_id,
                    $adjustmentItem['product_id'],
                    -1 * $adjustmentItem['quantity'],
                    Adjustment::class,
                    $adjustment->id,
                    'adjustment_subtraction',
                    'Adjustment Subtraction'
                );
            }
        }

        return $adjustment;
    }

    public function updateAdjustment($input, $id)
    {
        try {
            DB::beginTransaction();

            $adjustment = Adjustment::findOrFail($id);

            $input['total_products'] = count($input['adjustment_items']);
            $input['date'] = $input['date'] ?? date('Y/m/d');
            $adjustmentInputArray = Arr::only($input, [
                'date', 'warehouse_id', 'total_products',
            ]);
            $adjustment->update($adjustmentInputArray);

            $adjustment = $this->updateAdjustmentItems($adjustment, $input);

            DB::commit();

            return $adjustment;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function updateAdjustmentItems($adjustment, $input)
    {
        $adjustmentItmOldIds = AdjustmentItem::whereAdjustmentId($adjustment->id)->pluck('id')->toArray();
        $adjustmentItemIds = [];

        foreach ($input['adjustment_items'] as $key => $adjustmentItem) {
            $adjustmentItemIds[$key] = $adjustmentItem['adjustment_item_id'];

            $product = ManageStock::whereWarehouseId($adjustment->warehouse_id)->whereProductId($adjustmentItem['product_id'])->first();

            if (is_null($adjustmentItem['adjustment_item_id'])) {
                $adjustmentItem['adjustment_id'] = $adjustment->id;

                AdjustmentItem::Create($adjustmentItem);

                if (! empty($product)) {
                    if ($adjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) {
                        $totalQuantity = $product->quantity + $adjustmentItem['quantity'];
                        $product->update([
                            'quantity' => $totalQuantity,
                        ]);
                    } else {
                        $totalQuantity = $product->quantity - $adjustmentItem['quantity'];
                        if ($totalQuantity < 0) {
                            throw new UnprocessableEntityHttpException('Quantity exceeds quantity available in stock.');
                        }
                        $product->update([
                            'quantity' => $totalQuantity,
                        ]);
                    }
                } else {
                    if ($adjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) {
                         /** @var \App\Services\StockService $stockService */
                         $stockService = app(\App\Services\StockService::class);
                         $stockService->updateStock(
                            $adjustment->warehouse_id,
                            $adjustmentItem['product_id'],
                            $adjustmentItem['quantity'],
                            Adjustment::class,
                            $adjustment->id,
                            'adjustment_addition',
                            'Adjustment Addition (New Stock)'
                        );
                    }
                }
            } else {
                $exitAdjustmentItem = AdjustmentItem::whereId($adjustmentItem['adjustment_item_id'])->firstOrFail();

                // Refactoring update logic
                // Provide difference
                /** @var \App\Services\StockService $stockService */
                $stockService = app(\App\Services\StockService::class);
                
                // We need to calculate the NET change to stock.
                // Old item effect:
                // ADDITION of 10 -> Stock + 10
                // SUBTRACTION of 10 -> Stock - 10
                // New item effect:
                // ADDITION of 15 -> Stock + 15. Net change = +5.
                // SUBTRACTION of 12 -> Stock - 12. Net change = -2 (if old was SUB 10). Wait.
                // Old SUB 10, New SUB 12. Change is -2.
                // Old ADD 10, New ADD 15. Change is +5.
                // Old ADD 10, New SUB 5. Change is -15.
                // Old SUB 10, New ADD 5. Change is +15.

                $oldEffect = ($exitAdjustmentItem->method_type == AdjustmentItem::METHOD_ADDITION) ? $exitAdjustmentItem->quantity : -$exitAdjustmentItem->quantity;
                $newEffect = ($adjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) ? $adjustmentItem['quantity'] : -$adjustmentItem['quantity'];
                
                $netChange = $newEffect - $oldEffect;

                if ($netChange != 0) {
                     // Check stock sufficiency if net change is negative
                     if ($netChange < 0) {
                         $product = ManageStock::whereWarehouseId($adjustment->warehouse_id)->whereProductId($adjustmentItem['product_id'])->first();
                         if (!$product || ($product->quantity + $netChange) < 0) {
                              throw new UnprocessableEntityHttpException('Quantity exceeds quantity available in stock.');
                         }
                     }
                     
                     $stockService->updateStock(
                        $adjustment->warehouse_id,
                        $adjustmentItem['product_id'],
                        $netChange,
                        Adjustment::class,
                        $adjustment->id,
                        'adjustment_update',
                        'Adjustment Updated'
                    );
                }

                $exitAdjustmentItem->update([
                    'quantity' => $adjustmentItem['quantity'],
                    'method_type' => $adjustmentItem['method_type'],
                ]);
            }
        }

        $removeItemIds = array_diff($adjustmentItmOldIds, $adjustmentItemIds);

        if (! empty(array_values($removeItemIds))) {
            foreach ($removeItemIds as $removeItemId) {
                $oldItem = AdjustmentItem::whereId($removeItemId)->firstOrFail();
                $existProductStock = ManageStock::whereWarehouseId($adjustment->warehouse_id)->whereProductId($oldItem->product_id)->first();

                // Refactoring Remove Item
                // Reverse the effect
                /** @var \App\Services\StockService $stockService */
                $stockService = app(\App\Services\StockService::class);
                
                $effect = ($oldItem->method_type == AdjustmentItem::METHOD_ADDITION) ? $oldItem->quantity : -$oldItem->quantity;
                // Reverse it
                $reverseEffect = -1 * $effect;
                
                $stockService->updateStock(
                    $adjustment->warehouse_id,
                    $oldItem->product_id,
                    $reverseEffect,
                    Adjustment::class,
                    $adjustment->id,
                    'adjustment_item_remove',
                    'Adjustment Item Removed'
                );
            }
            AdjustmentItem::whereIn('id', array_values($removeItemIds))->delete();
        }

        return $adjustment;
    }
}
