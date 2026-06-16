<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Helpers\StockHelper;
use Illuminate\Support\Facades\Log;

class ProcessAdjustmentItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $adjustmentItems;
    protected $warehouseId;

    /**
     * Create a new job instance.
     *
     * @param array $adjustmentItems
     * @param int $warehouseId
     */
    public function __construct($adjustmentItems, $warehouseId)
    {
        $this->adjustmentItems = $adjustmentItems;
        $this->warehouseId = $warehouseId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info("Starting ProcessAdjustmentItems job", [
                'warehouse_id' => $this->warehouseId,
                'items_count' => count($this->adjustmentItems)
            ]);

            // Bulk load product codes to avoid N+1 queries
            $productIds = array_column($this->adjustmentItems, 'product_id');
            $products = Product::whereIn('id', $productIds)->select('id', 'code')->get()->keyBy('id');

            $visitedProductCodes = [];
            $visitedComboCodes = [];

            // Process each adjustment item
            foreach ($this->adjustmentItems as $item) {
                $productId = $item['product_id'];
                $product = $products->get($productId);

                if (!$product || !$product->code) {
                    continue; // Skip to the next item
                }

                // Adjust stock using the code and warehouse_id
                StockHelper::manageStockForCodeAndWarehouse(
                    $product->code,
                    $this->warehouseId,
                    $visitedProductCodes,
                    $visitedComboCodes
                );
            }

            Log::info("ProcessAdjustmentItems job completed successfully");
        } catch (\Exception $e) {
            Log::error('Error processing adjustment items: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
