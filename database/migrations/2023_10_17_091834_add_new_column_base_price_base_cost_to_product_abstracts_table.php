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
        Schema::table('product_abstracts', function (Blueprint $table) {
            $table->double('base_cost');
            $table->double('base_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_abstracts', function (Blueprint $table) {
            $table->dropColumn('base_cost');
            $table->dropColumn('base_price');
        });
    }
};
