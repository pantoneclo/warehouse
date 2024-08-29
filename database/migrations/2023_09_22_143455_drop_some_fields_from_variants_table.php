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
        Schema::table('variants', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['product_id']);

            // Drop the column
            $table->dropColumn('variant_cost');
            $table->dropColumn('product_id');
            $table->dropColumn('code');
            $table->dropColumn('barcode_symbol');
            $table->dropColumn('variant_price');
            $table->dropColumn('notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->double('variant_cost');
            $table->double('variant_price');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('code')->unique()->nullable();
            $table->integer('barcode_symbol')->default(1);

            $table->text('notes')->nullable();
        });
    }
};
