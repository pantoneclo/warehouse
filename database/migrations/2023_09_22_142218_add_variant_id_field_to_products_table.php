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
            //
            $table->unsignedBigInteger('variant_id')->after('id')->nullable()->default(null);
            $table->foreign('variant_id')->references('id')
                ->on('variants')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unsignedBigInteger('product_abstract_id')->after('id')->nullable()->default(null);
            $table->foreign('product_abstract_id')->references('id')
                ->on('product_abstracts')
                ->onUpdate('cascade')
                ->onDelete('cascade');
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
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');

            $table->dropForeign(['product_abstract_id']);
            $table->dropColumn('product_abstract_id');
        });
    }
};
