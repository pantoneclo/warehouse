<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestPostgreSQLCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:postgresql {--sku=PR_002A90007F : SKU to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PostgreSQL connection and stock updates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sku = $this->option('sku');
        
        $this->info("Testing PostgreSQL connection and stock updates...");
        
        try {
            // Test basic connection
            $this->info("1. Testing PostgreSQL connection...");
            $result = DB::connection('pgsql')->select('SELECT version()');
            $this->info("✅ PostgreSQL connected successfully!");
            $this->info("   Version: " . $result[0]->version);
            
            // Test product_meta table
            $this->info("\n2. Testing product_meta table...");
            $count = DB::connection('pgsql')->table('product_meta')->count();
            $this->info("✅ Found {$count} records in product_meta table");
            
            // Test specific SKU search
            $this->info("\n3. Testing SKU search for: {$sku}");
            $productMetaItems = DB::connection('pgsql')->table('product_meta')
                ->select('id', 'variants')
                ->where('country_id', '!=', 1) // For warehouse 1
                ->whereRaw("variants::jsonb @> ?", [json_encode([['variantDetails' => [['sku' => $sku]]]])])
                ->get();
                
            $this->info("✅ Found " . $productMetaItems->count() . " product_meta records for SKU: {$sku} (warehouse 1)");
            
            // Test for warehouse 3
            $productMetaItems3 = DB::connection('pgsql')->table('product_meta')
                ->select('id', 'variants')
                ->where('country_id', '=', 1) // For warehouse 3
                ->whereRaw("variants::jsonb @> ?", [json_encode([['variantDetails' => [['sku' => $sku]]]])])
                ->get();
                
            $this->info("✅ Found " . $productMetaItems3->count() . " product_meta records for SKU: {$sku} (warehouse 3)");
            
            // Show sample data if found
            if ($productMetaItems->count() > 0) {
                $this->info("\n4. Sample data for warehouse 1:");
                $sample = $productMetaItems->first();
                $variants = json_decode($sample->variants, true);
                $this->info("   Product Meta ID: {$sample->id}");
                $this->info("   Variants count: " . count($variants));
            }
            
            if ($productMetaItems3->count() > 0) {
                $this->info("\n5. Sample data for warehouse 3:");
                $sample = $productMetaItems3->first();
                $variants = json_decode($sample->variants, true);
                $this->info("   Product Meta ID: {$sample->id}");
                $this->info("   Variants count: " . count($variants));
            }
            
            $this->info("\n✅ All PostgreSQL tests completed successfully!");
            
        } catch (\Exception $e) {
            $this->error("❌ PostgreSQL test failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
