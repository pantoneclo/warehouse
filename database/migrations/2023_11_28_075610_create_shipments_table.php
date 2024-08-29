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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('pickup_address_id')->nullable();
            $table->unsignedBigInteger('delivery_address_id')->nullable();

            $table->integer('parcel_company_id')->default(null);

            $table->string('parcel_id')->default(null);
            $table->bigInteger('parcel_number')->default(null);
            $table->string('cod_amount')->default(null);
            $table->string('cod_reference')->default(null);
            $table->string('client_reference')->default(null);
            $table->string('count')->default(null);
            $table->string('content')->default(null);
            $table->string('pickup_date');

            $table->string('status_description')->default(null)->comment('delivery status');
            $table->string('depot_city')->default(null)->comment('delivery status on city');
            $table->string('status_date')->default(null)->comment('delivery status date');
            $table->string('weight')->default(null)->comment('delivery product weight');

            $table->foreign('sale_id')->references('id')->on('sales')->ondelete('cascade');
            $table->foreign('pickup_address_id')->references('id')->on('addresses')->ondelete('cascade');
            $table->foreign('delivery_address_id')->references('id')->on('addresses')->ondelete('cascade');
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
        Schema::dropIfExists('shipments');
    }
};
