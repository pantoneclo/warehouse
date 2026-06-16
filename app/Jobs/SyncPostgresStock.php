<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\StockHelper;
use Illuminate\Support\Facades\Log;

class SyncPostgresStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 3;

    protected $warehouseId;
    protected $skus;

    /**
     * Create a new job instance.
     *
     * @param int $warehouseId
     * @param array $skus
     */
    public function __construct($warehouseId, array $skus)
    {
        $this->warehouseId = $warehouseId;
        $this->skus = array_unique(array_filter($skus));
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("Starting SyncPostgresStock job", [
            'warehouse_id' => $this->warehouseId,
            'skus_count' => count($this->skus)
        ]);

        $visitedProductCodes = [];
        $visitedComboCodes = [];

        foreach ($this->skus as $code) {
            try {
                StockHelper::manageStockForCodeAndWarehouse(
                    $code,
                    $this->warehouseId,
                    $visitedProductCodes,
                    $visitedComboCodes
                );
            } catch (\Exception $e) {
                Log::error("Failed remote pgsql stock update in SyncPostgresStock job for SKU {$code}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info("SyncPostgresStock job completed successfully");
    }
}
