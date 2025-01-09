<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Http\Controllers\API\StockManagementAPIController;

class ProcessAdjustmentItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $adjustmentItems;
    protected $warehouseId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($adjustmentItems, $warehouseId)
    {
        $this->adjustmentItems = $adjustmentItems;
        $this->warehouseId = $warehouseId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(StockManagementAPIController $stockManagement)
    {
        try {
            // Process each adjustment item
            foreach ($this->adjustmentItems as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                $methodType = $item['method_type']; // 1 for increase, 0 for decrease

                // Fetch the product code using the product_id
                $productCode = Product::where('id', $productId)->value('code');
                if (!$productCode) {
                    // Handle product not found case
                    continue; // Skip to the next item
                }

                // Adjust stock using the code and warehouse_id
                $stockManagement->manageStockForCodeAndWarehouse($productCode, $this->warehouseId);

                // Optional: Log the adjustment or perform other operations here
            }
        } catch (\Exception $e) {
            // Handle any exceptions that occur during the background processing
            \Log::error('Error processing adjustment items: ' . $e->getMessage());
        }
    }
}
