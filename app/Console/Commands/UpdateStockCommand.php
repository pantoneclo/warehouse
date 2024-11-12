<?php

namespace App\Console\Commands;

use App\Models\Warehouse;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Product;
use App\Models\ManageStock;
use App\Services\ApiService;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\API\StockManagementAPIController;

class UpdateStockCommand extends Command
{
    protected $signature = 'stock:update';
    protected $description = 'Update stock quantities in the external system by product warehouse';
    protected $stockManagement;

    public function __construct(StockManagementAPIController $stockManagement)
    {
        parent::__construct();
        $this->stockManagement = $stockManagement;
    }

    public function handle()
    {
        $warehouses = Warehouse::all();
        foreach ($warehouses as $warehouse) {
            $this->stockManagement->stockManagedbyWarehouse($warehouse->id);
        }
    }


}
