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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('city')->default(NULL) ;
            $table->string('contact_email')->default(NULL);
            $table->string('contact_name')->default(NULL);
            $table->string('contact_phone')->default(NULL);     
            $table->string('country_iso_code')->default(NULL);
            $table->string('house_number')->default(NULL);
            $table->string('name')->default(NULL) ->comment ('pickup or delivery adress');
            $table->string('street')->default(NULL);
            $table->string('zip_code')->default(NULL);
            $table->string('house_number_info')->default(NULL);
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
        Schema::dropIfExists('addresses');
    }
};
