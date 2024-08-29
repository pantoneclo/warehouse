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
            //
            $table->string('style', 30)->nullable()->default(NULL);
            $table->string('pan_style', 30)->nullable()->default(NULL);
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
            //
            $table->dropColumn('style');
            $table->dropColumn('pan_style');
        });
    }
};
