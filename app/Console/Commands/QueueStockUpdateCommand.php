<?php

namespace App\Console\Commands;

use App\Jobs\ProcessStockUpdate;
use App\Models\Product;
use App\Models\ComboProduct;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class QueueStockUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:queue-update {--warehouse-id= : Specific warehouse ID to process} {--batch-size=50 : Number of items per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue stock update jobs for processing with queue workers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = now();
        $this->info("Starting queue-based stock update at {$startTime->format('Y-m-d H:i:s')}");
        
        $warehouseId = $this->option('warehouse-id');
        $batchSize = (int) $this->option('batch-size');
        
        if ($warehouseId) {
            $this->queueWarehouse($warehouseId, $batchSize);
        } else {
            // Process warehouses 1 and 3
            $this->queueWarehouse(1, $batchSize);
            $this->queueWarehouse(3, $batchSize);
        }
        
        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);
        
        $this->info("Stock update jobs queued successfully!");
        $this->info("Duration: {$duration} seconds");
        $this->info("Jobs will be processed by queue workers.");
        
        return Command::SUCCESS;
    }

    /**
     * Queue jobs for specific warehouse
     */
    private function queueWarehouse($warehouseId, $batchSize)
    {
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            $this->error("Warehouse with ID {$warehouseId} not found");
            return;
        }

        $this->info("Queueing jobs for warehouse: {$warehouse->name} (ID: {$warehouseId})");

        // Get all unique product codes for this warehouse
        $productCodes = Product::whereHas('stocks', function($query) use ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })->pluck('code')->unique();

        // Get all unique combo codes for this warehouse
        $comboCodes = ComboProduct::where('warehouse_id', $warehouseId)
            ->pluck('code')
            ->unique();

        $totalItems = $productCodes->count() + $comboCodes->count();
        $this->info("Found {$productCodes->count()} products and {$comboCodes->count()} combos to queue");

        $jobsQueued = 0;

        // Queue product jobs in batches
        $productBatches = $productCodes->chunk($batchSize);
        foreach ($productBatches as $batch) {
            foreach ($batch as $productCode) {
                ProcessStockUpdate::dispatch($productCode, $warehouseId);
                $jobsQueued++;
            }
        }

        // Queue combo jobs in batches
        $comboBatches = $comboCodes->chunk($batchSize);
        foreach ($comboBatches as $batch) {
            foreach ($batch as $comboCode) {
                ProcessStockUpdate::dispatch($comboCode, $warehouseId);
                $jobsQueued++;
            }
        }

        $this->info("Queued {$jobsQueued} jobs for warehouse: {$warehouse->name}");
        
        Log::info("Queued stock update jobs", [
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouse->name,
            'jobs_queued' => $jobsQueued,
            'products' => $productCodes->count(),
            'combos' => $comboCodes->count()
        ]);
    }
}
