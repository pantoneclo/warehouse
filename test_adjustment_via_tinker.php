<?php

use App\Models\Product;
use App\Models\ManageStock;
use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Repositories\AdjustmentRepository;
use App\Jobs\ProcessAdjustmentItems;

// Get product and current stock
$productId = 3251; // PR_0028600224
$product = Product::find($productId);
$warehouseId = 3;

$initialStock = ManageStock::where('warehouse_id', $warehouseId)->where('product_id', $productId)->value('quantity') ?? 0;
echo "Initial local stock in warehouse 3: " . $initialStock . "\n";

// 1. Test Store Adjustment
$input = [
    'date' => date('Y-m-d'),
    'warehouse_id' => $warehouseId,
    'adjustment_items' => [
        [
            'product_id' => $productId,
            'quantity' => 2,
            'method_type' => 1, // Addition
        ]
    ]
];

$repository = app(AdjustmentRepository::class);

try {
    echo "Storing adjustment...\n";
    $adjustment = $repository->storeAdjustment($input);
    echo "Adjustment stored with ID: " . $adjustment->id . "\n";

    // Verify local stock updated immediately
    $newStock = ManageStock::where('warehouse_id', $warehouseId)->where('product_id', $productId)->value('quantity') ?? 0;
    echo "Stock after store (should be initial + 2): " . $newStock . "\n";

    // Check if background job was dispatched by dispatching manually or checking queue (in Controller it is dispatched, here we dispatch directly)
    echo "Dispatching ProcessAdjustmentItems background job...\n";
    ProcessAdjustmentItems::dispatch($input['adjustment_items'], $warehouseId);

    // Run queue worker once to process the job
    echo "Running queue worker...\n";
    Artisan::call('queue:work', ['--once' => true]);
    echo "Queue worker execution completed.\n";

    // 2. Test update
    $updateInput = [
        'date' => date('Y-m-d'),
        'warehouse_id' => $warehouseId,
        'adjustment_items' => [
            [
                'adjustment_item_id' => $adjustment->adjustmentItems[0]->id,
                'product_id' => $productId,
                'quantity' => 5, // Changed from 2 to 5 (net change is +3)
                'method_type' => 1,
            ]
        ]
    ];

    echo "Updating adjustment...\n";
    $adjustment = $repository->updateAdjustment($updateInput, $adjustment->id);
    
    $updatedStock = ManageStock::where('warehouse_id', $warehouseId)->where('product_id', $productId)->value('quantity') ?? 0;
    echo "Stock after update (should be initial + 5): " . $updatedStock . "\n";

    echo "Dispatching ProcessAdjustmentItems for update...\n";
    ProcessAdjustmentItems::dispatch($updateInput['adjustment_items'], $warehouseId);
    Artisan::call('queue:work', ['--once' => true]);

    // 3. Test delete/destroy
    echo "Deleting adjustment...\n";
    // We will simulate the Controller's destroy method
    DB::beginTransaction();
    $adjustmentReloaded = Adjustment::with('adjustmentItems')->find($adjustment->id);
    foreach ($adjustmentReloaded->adjustmentItems as $adjustmentItem) {
        $oldItem = AdjustmentItem::whereId($adjustmentItem->id)->firstOrFail();
        $existProductStock = ManageStock::whereWarehouseId($adjustmentReloaded->warehouse_id)->whereProductId($oldItem->product_id)->first();

        if ($oldItem->method_type == AdjustmentItem::METHOD_ADDITION) {
            $totalQuantity = $existProductStock->quantity - $oldItem['quantity'];
        } else {
            $totalQuantity = $existProductStock->quantity + $oldItem['quantity'];
        }

        $existProductStock->update([
            'quantity' => $totalQuantity,
        ]);
    }
    $repository->delete($adjustmentReloaded->id);
    DB::commit();

    $deletedStock = ManageStock::where('warehouse_id', $warehouseId)->where('product_id', $productId)->value('quantity') ?? 0;
    echo "Stock after delete (should be initial): " . $deletedStock . "\n";

    echo "Dispatched background job after delete...\n";
    // Dispatch SyncPostgresStock for SKU
    App\Jobs\SyncPostgresStock::dispatch($warehouseId, [$product->code]);
    Artisan::call('queue:work', ['--once' => true]);
    echo "All tests finished successfully!\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
