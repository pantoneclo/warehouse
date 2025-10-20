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
        Schema::table('countries', function (Blueprint $table) {
            // Add currency relationship to countries
            if (!Schema::hasColumn('countries', 'currency_id')) {
                $table->unsignedBigInteger('currency_id')->nullable()->after('phone_code')->comment('Default currency for this country');
                $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            }
            
            // Add currency code for quick reference
            if (!Schema::hasColumn('countries', 'currency_code')) {
                $table->string('currency_code', 3)->nullable()->after('currency_id')->comment('Currency code (USD, EUR, BDT)');
            }
            
            // Add index for better performance
            $table->index('currency_id');
            $table->index('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            // Drop foreign key and indexes first
            if (Schema::hasColumn('countries', 'currency_id')) {
                $table->dropForeign(['currency_id']);
                $table->dropIndex(['currency_id']);
                $table->dropColumn('currency_id');
            }
            
            if (Schema::hasColumn('countries', 'currency_code')) {
                $table->dropIndex(['currency_code']);
                $table->dropColumn('currency_code');
            }
        });
    }
};
