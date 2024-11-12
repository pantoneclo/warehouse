<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Warehouse;
use App\Http\Controllers\API\StockManagementAPIController;

class ComboUpdateStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:comboupdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Combo Product  Stock Update';

    /**
     * Execute the console command.
     *
     * @return int
     */

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
            $this->stockManagement->comboStockManagedBySku($warehouse->id);
        }
    }
}
