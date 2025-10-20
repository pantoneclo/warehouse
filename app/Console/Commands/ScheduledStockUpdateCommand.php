<?php

namespace App\Console\Commands;

use App\Helpers\StockHelper;
use App\Models\Product;
use App\Models\ComboProduct;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ScheduledStockUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:scheduled-update {--warehouse-id= : Specific warehouse ID to process} {--product-code= : Specific product code to process} {--combo-code= : Specific combo code to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scheduled stock update at 3 AM BDT - Updates stock quantities for all products and combos across warehouses';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = now();
        $bdtTime = $startTime->setTimezone('Asia/Dhaka');
        $this->info("Starting scheduled stock update at {$bdtTime->format('Y-m-d H:i:s')} BDT");

        Log::info("Scheduled stock update started at {$bdtTime->format('Y-m-d H:i:s')} BDT");

        // Set memory and execution limits for large operations
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '2G');

        try {
            // Get command options
            $warehouseId = $this->option('warehouse-id');
            $productCode = $this->option('product-code');
            $comboCode = $this->option('combo-code');

            if ($warehouseId && $productCode) {
                // Process specific product in specific warehouse
                $this->processSingleProductWarehouse($productCode, $warehouseId);
            } elseif ($warehouseId && $comboCode) {
                // Process specific combo in specific warehouse
                $this->processSingleComboWarehouse($comboCode, $warehouseId);
            } elseif ($warehouseId) {
                // Process all products/combos in specific warehouse
                $this->processWarehouse($warehouseId);
            } else {
                // Process all warehouses (default scheduled behavior)
                $this->processAllWarehouses();
            }

            $endTime = now();
            $bdtEndTime = $endTime->setTimezone('Asia/Dhaka');
            $duration = $endTime->diffInSeconds($startTime);

            $this->info("Scheduled stock update completed successfully!");
            $this->info("End time: {$bdtEndTime->format('Y-m-d H:i:s')} BDT");
            $this->info("Duration: {$duration} seconds");

            Log::info("Scheduled stock update completed successfully", [
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $bdtEndTime->format('Y-m-d H:i:s'),
                'duration_seconds' => $duration,
                'warehouse_id' => $warehouseId,
                'product_code' => $productCode,
                'combo_code' => $comboCode
            ]);
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error during scheduled stock update: " . $e->getMessage());
            Log::error("Scheduled stock update failed: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Process all warehouses (specifically warehouse 1 and 3)
     */
    private function processAllWarehouses()
    {
        // Focus on the two main warehouses: 1 and 3
        $warehouseIds = [1, 3];
        $warehouses = Warehouse::whereIn('id', $warehouseIds)->get();

        $this->info("Processing " . $warehouses->count() . " main warehouses (ID: 1 and 3)...");

        foreach ($warehouses as $warehouse) {
            $this->info("Processing warehouse: {$warehouse->name} (ID: {$warehouse->id})");
            $this->processWarehouse($warehouse->id);
        }

        // Log which warehouses were processed
        Log::info("Processed warehouses", [
            'warehouse_ids' => $warehouseIds,
            'processed_count' => $warehouses->count()
        ]);
    }

    /**
     * Process specific warehouse
     */
    private function processWarehouse($warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            $this->error("Warehouse with ID {$warehouseId} not found");
            return;
        }

        $this->info("Processing warehouse: {$warehouse->name} (ID: {$warehouseId})");

        // Get all unique product codes for this warehouse
        $productCodes = Product::whereHas('stocks', function($query) use ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })->pluck('code')->unique();

        // Get all unique combo codes for this warehouse
        $comboCodes = ComboProduct::where('warehouse_id', $warehouseId)
            ->pluck('code')
            ->unique();

        $totalItems = $productCodes->count() + $comboCodes->count();
        $this->info("Found {$productCodes->count()} products and {$comboCodes->count()} combos to process");

        $progressBar = $this->output->createProgressBar($totalItems);
        $progressBar->start();

        // Process all products
        foreach ($productCodes as $productCode) {
            $visitedProductCodes = [];
            $visitedComboCodes = [];
            
            StockHelper::manageStockForCodeAndWarehouse(
                $productCode, 
                $warehouseId, 
                $visitedProductCodes, 
                $visitedComboCodes
            );
            
            $progressBar->advance();
        }

        // Process all combos
        foreach ($comboCodes as $comboCode) {
            $visitedProductCodes = [];
            $visitedComboCodes = [];
            
            StockHelper::manageStockForCodeAndWarehouse(
                $comboCode, 
                $warehouseId, 
                $visitedProductCodes, 
                $visitedComboCodes
            );
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Completed warehouse: {$warehouse->name}");
    }

    /**
     * Process single product in specific warehouse
     */
    private function processSingleProductWarehouse($productCode, $warehouseId)
    {
        $this->info("Processing single product: {$productCode} in warehouse: {$warehouseId}");
        
        $visitedProductCodes = [];
        $visitedComboCodes = [];
        
        StockHelper::manageStockForCodeAndWarehouse(
            $productCode, 
            $warehouseId, 
            $visitedProductCodes, 
            $visitedComboCodes
        );
        
        $this->info("Completed processing product: {$productCode}");
    }

    /**
     * Process single combo in specific warehouse
     */
    private function processSingleComboWarehouse($comboCode, $warehouseId)
    {
        $this->info("Processing single combo: {$comboCode} in warehouse: {$warehouseId}");
        
        $visitedProductCodes = [];
        $visitedComboCodes = [];
        
        StockHelper::manageStockForCodeAndWarehouse(
            $comboCode, 
            $warehouseId, 
            $visitedProductCodes, 
            $visitedComboCodes
        );
        
        $this->info("Completed processing combo: {$comboCode}");
    }
}
