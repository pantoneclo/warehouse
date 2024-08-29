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
            $table->string('country')->default(null)->comment('Country Name where they sale');
            $table->string('order_no')->default(null);
            $table->string('market_place')->default(null);
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

            $table->dropColumn('country');
            $table->dropColumn('order_no');
            $table->dropColumn('market_place');
        });
    }
};
