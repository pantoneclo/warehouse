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
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['product_category_id']);
            $table->dropForeign(['brand_id']);

            // Drop the column
            $table->dropColumn('product_category_id');
            $table->dropColumn('brand_id');
            $table->dropColumn('product_unit');
            $table->dropColumn('sale_unit');
            $table->dropColumn('purchase_unit');
            $table->dropColumn('order_tax');
            $table->dropColumn('tax_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            //
            $table->string('product_unit');
            $table->string('sale_unit')->nullable();
            $table->string('purchase_unit')->nullable();

            $table->unsignedBigInteger('product_category_id');
            $table->foreign('product_category_id')->references('id')
                ->on('product_categories')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unsignedBigInteger('brand_id');
            $table->foreign('brand_id')->references('id')
                ->on('brands')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }
};
