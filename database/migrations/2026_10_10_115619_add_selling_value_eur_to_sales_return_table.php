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
        Schema::table('sales_return', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_return', 'selling_value_eur')) {
                $table->decimal('selling_value_eur', 15, 4)->nullable()->after('grand_total')->comment('Selling value in EUR');
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
        Schema::table('sales_return', function (Blueprint $table) {
            if (Schema::hasColumn('sales_return', 'selling_value_eur')) {
                $table->dropColumn('selling_value_eur');
            }
        });
    }
};
