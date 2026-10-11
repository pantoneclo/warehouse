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
            if (!Schema::hasColumn('sales', 'selling_value_usd')) {
                $table->decimal('selling_value_usd', 15, 4)->nullable()->after('selling_value_eur');
            }
        });

        Schema::table('sales_return', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_return', 'selling_value_usd')) {
                $table->decimal('selling_value_usd', 15, 4)->nullable()->after('selling_value_eur');
            }
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
            if (Schema::hasColumn('sales', 'selling_value_usd')) {
                $table->dropColumn('selling_value_usd');
            }
        });

        Schema::table('sales_return', function (Blueprint $table) {
            if (Schema::hasColumn('sales_return', 'selling_value_usd')) {
                $table->dropColumn('selling_value_usd');
            }
        });
    }
};
