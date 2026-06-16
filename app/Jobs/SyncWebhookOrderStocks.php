<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helpers\StockHelper;
use App\Services\ApiService;

class SyncWebhookOrderStocks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 3;

    protected $warehouseId;
    protected $countryCode;
    protected $operation;
    protected $preparedItems;
    protected $comboRelatedItems;
    protected $skusToSync;

    /**
     * Create a new job instance.
     *
     * @param int $warehouseId
     * @param string $countryCode
     * @param string $operation
     * @param array $preparedItems
     * @param array $comboRelatedItems
     * @param array $skusToSync
     */
    public function __construct($warehouseId, $countryCode, $operation, $preparedItems, $comboRelatedItems, $skusToSync)
    {
        $this->warehouseId = $warehouseId;
        $this->countryCode = $countryCode;
        $this->operation = $operation;
        $this->preparedItems = $preparedItems;
        $this->comboRelatedItems = $comboRelatedItems;
        $this->skusToSync = $skusToSync;
    }

    /**
     * Execute the job.
     */
    public function handle(ApiService $apiService)
    {
        Log::info("Starting SyncWebhookOrderStocks job", [
            'warehouse_id' => $this->warehouseId,
            'country_code' => $this->countryCode,
            'operation' => $this->operation,
            'prepared_items_count' => count($this->preparedItems),
            'combo_related_items_count' => count($this->comboRelatedItems),
            'skus_to_sync_count' => count($this->skusToSync),
        ]);

        // 1. External Stock API synchronization
        if (!empty($this->preparedItems)) {
            $this->syncExternalStock($apiService, $this->countryCode, $this->operation, $this->preparedItems);
        }

        if (!empty($this->comboRelatedItems)) {
            $this->syncExternalStock($apiService, $this->countryCode, $this->operation, $this->comboRelatedItems);
        }

        // 2. Remote PostgreSQL product_meta update
        foreach ($this->skusToSync as $code) {
            try {
                $visitedProductCodes = [];
                $visitedComboCodes = [];
                StockHelper::manageStockForCodeAndWarehouse(
                    $code,
                    $this->warehouseId,
                    $visitedProductCodes,
                    $visitedComboCodes
                );
            } catch (\Exception $e) {
                Log::error("Failed remote pgsql stock update in job for SKU {$code}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info("SyncWebhookOrderStocks job completed successfully");
    }

    /**
     * Sync external stock api service helper
     */
    protected function syncExternalStock(ApiService $apiService, $warehouse, $operation, $items)
    {
        try {
            $token = Cache::get('api_token');

            if (!$token) {
                $loginResponse = $apiService->login('admin@gmail.com', 'Admin@123', 1);
                if ($loginResponse['isSuccess']) {
                    $token = Cache::get('api_token');
                } else {
                    Log::error("Login failed in SyncWebhookOrderStocks job, skipping external sync.");
                    return;
                }
            }

            $response = $apiService->manageStockBySku($warehouse, $operation, $items);
            Log::info("External stock sync response received", ['response' => $response]);
        } catch (\Exception $e) {
            Log::error("External stock sync API call failed in job: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
