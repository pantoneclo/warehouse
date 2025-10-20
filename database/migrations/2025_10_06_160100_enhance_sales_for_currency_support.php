<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('sales', 'country_id')) {
                $table->unsignedInteger('country_id')->nullable()->after('warehouse_id')->comment('Country ID for currency calculation');
                $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            }

            if (!Schema::hasColumn('sales', 'currency_id')) {
                $table->unsignedInteger('currency_id')->nullable()->after('country_id')->comment('Currency ID used for this sale');
                $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            }
            
            // Enhance existing currency field if it's just a string
            if (Schema::hasColumn('sales', 'currency') && Schema::getColumnType('sales', 'currency') === 'string') {
                $table->string('currency', 3)->nullable()->change()->comment('Currency code (USD, EUR, BDT)');
            }
            
            // Enhance conversion_rate precision if it exists
            if (Schema::hasColumn('sales', 'conversion_rate')) {
                $table->decimal('conversion_rate', 10, 4)->default(1.0000)->change()->comment('Currency conversion rate');
            }
            
            // Add grand_total_original if it doesn't exist
            if (!Schema::hasColumn('sales', 'grand_total_original')) {
                $table->decimal('grand_total_original', 15, 4)->nullable()->after('grand_total')->comment('Grand total in original currency before conversion');
            }
            
            // Add indexes for better performance
            $table->index('country_id');
            $table->index('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('sales', 'country_id')) {
                $table->dropForeign(['country_id']);
                $table->dropIndex(['country_id']);
                $table->dropColumn('country_id');
            }
            
            if (Schema::hasColumn('sales', 'currency_id')) {
                $table->dropForeign(['currency_id']);
                $table->dropIndex(['currency_id']);
                $table->dropColumn('currency_id');
            }
            
            if (Schema::hasColumn('sales', 'grand_total_original')) {
                $table->dropColumn('grand_total_original');
            }
        });
    }
};
