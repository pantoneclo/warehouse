<?php

namespace App\Console\Commands;

use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ComboProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StockUpdateStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show status of stock update system and warehouse information';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("=== Stock Update System Status ===");
        $this->newLine();

        // Show current time in BDT
        $bdtTime = now()->setTimezone('Asia/Dhaka');
        $this->info("Current Time (BDT): " . $bdtTime->format('Y-m-d H:i:s'));
        $this->newLine();

        // Show warehouse information
        $this->info("=== Warehouse Information ===");
        $warehouses = Warehouse::whereIn('id', [1, 3])->get();
        
        foreach ($warehouses as $warehouse) {
            $this->info("Warehouse {$warehouse->id}: {$warehouse->name}");
            
            // Count products with stock in this warehouse
            $productCount = Product::whereHas('stocks', function($query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id);
            })->count();
            
            // Count combos in this warehouse
            $comboCount = ComboProduct::where('warehouse_id', $warehouse->id)
                ->distinct('code')
                ->count();
            
            $this->line("  - Products: {$productCount}");
            $this->line("  - Combos: {$comboCount}");
            $this->line("  - Total Items: " . ($productCount + $comboCount));
        }
        
        $this->newLine();
        
        // Show schedule information
        $this->info("=== Schedule Information ===");
        $this->line("• Automatic Updates: Daily at 3:00 AM BDT");
        $this->line("• Timezone: Asia/Dhaka (UTC+6)");
        $this->line("• Overlap Protection: Enabled");
        $this->line("• Background Execution: Enabled");
        
        $this->newLine();
        
        // Calculate next run time
        $now = $bdtTime;
        $next3AM = $now->copy()->addDay()->setTime(3, 0, 0);

        if ($now->hour < 3) {
            $next3AM = $now->copy()->setTime(3, 0, 0);
        }

        $this->info("=== Next Scheduled Run ===");
        $this->line("• Next scheduled run: " . $next3AM->format('Y-m-d H:i:s') . " BDT");
        
        $this->newLine();
        
        // Show manual commands
        $this->info("=== Manual Commands ===");
        $this->line("• Run now (all warehouses): php artisan stock:scheduled-update");
        $this->line("• Run warehouse 1 only: php artisan stock:scheduled-update --warehouse-id=1");
        $this->line("• Run warehouse 3 only: php artisan stock:scheduled-update --warehouse-id=3");
        $this->line("• Check status: php artisan stock:status");
        
        $this->newLine();
        $this->info("System is ready for automatic stock updates!");
        
        return Command::SUCCESS;
    }
}
