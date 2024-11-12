<?php

namespace App\Jobs;


use App\Services\ApiService;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ManageStock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $operation;
    protected $stockManagementController;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $operation, $stockManagementController)
    {
        $this->data = $data;
        $this->operation = $operation;
        $this->stockManagementController = $stockManagementController;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Check if the necessary fields exist in the formatted purchase data
            if (isset($this->data['warehouse_id'])) {
                // Extract the necessary fields from the formatted data
                $warehouse_id = $this->data['warehouse_id'];
                $warehouse = Warehouse::find($warehouse_id);
                $warehouse_code = $warehouse->country_code;
                $operation = $this->operation; // Define the operation type
                $saleItems = $this->data->purchaseItems->toArray();

                if (empty($saleItems)) {
                    Log::error("Sale items are empty or not an array", ['saleItems' => $saleItems]);
                    return;
                }

                // Call stock management controller to handle inventory
                $this->stockManagementController->prepareStockItems($warehouse_id, $warehouse_code, $saleItems, $operation);
            } else {
                Log::error("Missing warehouse ID in purchase", ['purchase' => $this->data]);
            }
        } catch (\Exception $e) {
            Log::error('Error during stock management: ' . $e->getMessage());
        }
    }
}
