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
        Schema::create('product_abstracts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
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
            $table->json('attributes')->nullable();
            $table->string('product_unit');
            $table->string('sale_unit')->nullable();
            $table->string('purchase_unit')->nullable();
            $table->double('order_tax')->nullable();
            $table->enum('tax_type', [1, 2])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_abstracts');
    }
};
