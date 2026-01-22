<?php

namespace App\Services;

use App\Models\ManageStock;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\StockHelper;
use Exception;

class StockService
{
    /**
     * Update stock and log history
     *
     * @param int $warehouseId
     * @param int $productId
     * @param float $quantity Change amount (pos for add, neg for sub) or absolute amount if needed (but usually change)
     *                        Wait, the standard is usually to pass the change amount.
     *                        Let's verify how manageStock worked.
     *                        manageStock($warehouseID, $productID, $qty=0) adds $qty to existing.
     *                        So $quantity here should be the CHANGE amount.
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param string|null $action
     * @param string|null $note
     * @return ManageStock
     */
    public function updateStock($warehouseId, $productId, $quantity, $referenceType = null, $referenceId = null, $action = null, $note = null)
    {
        // Prevent updates with 0 quantity if desired, but sometimes 0 change might be relevant?
        // Usually 0 change means nothing to do.
        if ($quantity == 0) {
            // Retrieve current stock to return it? or just return null?
            // Better to fetch and return current stock state even if no change.
            return ManageStock::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['quantity' => 0]
            );
        }

        $manageStock = ManageStock::lockForUpdate()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['quantity' => 0]
        );

        $oldQuantity = $manageStock->quantity;
        $newQuantity = $oldQuantity + $quantity;

        if ($newQuantity < 0) {
            // Depending on strictness, we might throw error or allow negative stock.
            // Existing code seems to allow it or clamp to 0?
            // In manageStock helper: if (($product->quantity + $qty) < 0) { $totalQuantity = 0; }
            // So it clamps to 0.
            $newQuantity = 0;
            // Adjust the actual change amount tracked in history to reflect the clamping?
            // If I tried to subtract 10 from 5, result is 0. Actual change is -5.
            $quantity = $newQuantity - $oldQuantity;
        }

        $manageStock->update(['quantity' => $newQuantity]);

        // Create History
        StockHistory::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'action' => $action,
            'user_id' => Auth::id(),
            'note' => $note,
        ]);

        // Helper trigger to update product meta or other checks
        // The original helper calls StockHelper::manageStockForCodeAndWarehouse
        // We must ensure product is loaded to get code
        if (!$manageStock->relationLoaded('product')) {
            $manageStock->load('product');
        }
        
        if ($manageStock->product) {
             StockHelper::manageStockForCodeAndWarehouse($manageStock->product->code, $warehouseId);
        }

        return $manageStock;
    }
}
