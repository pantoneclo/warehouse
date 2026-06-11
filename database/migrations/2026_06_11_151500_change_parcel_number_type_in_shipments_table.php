<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('parcel_number')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->bigInteger('parcel_number')->nullable()->change();
        });
    }
};
