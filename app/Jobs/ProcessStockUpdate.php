<?php

namespace App\Jobs;

use App\Helpers\StockHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStockUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;

    protected $code;
    protected $warehouseId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($code, $warehouseId)
    {
        $this->code = $code;
        $this->warehouseId = $warehouseId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info("Processing stock update job for code: {$this->code}, warehouse: {$this->warehouseId}");

            $visitedProductCodes = [];
            $visitedComboCodes = [];

            StockHelper::manageStockForCodeAndWarehouse(
                $this->code,
                $this->warehouseId,
                $visitedProductCodes,
                $visitedComboCodes
            );

            Log::info("Completed stock update job for code: {$this->code}, warehouse: {$this->warehouseId}");

        } catch (\Exception $e) {
            Log::error("Stock update job failed for code: {$this->code}, warehouse: {$this->warehouseId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Stock update job permanently failed for code: {$this->code}, warehouse: {$this->warehouseId}", [
            'error' => $exception->getMessage()
        ]);
    }
}
