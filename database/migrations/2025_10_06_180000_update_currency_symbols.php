<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update currency symbols with proper Unicode symbols
        $currencyUpdates = [
            'BDT' => '৳',    // Bangladesh Taka
            'EUR' => '€',    // Euro
            'USD' => '$',    // US Dollar
            'PLN' => 'zł',   // Polish Zloty
            'HUF' => 'Ft',   // Hungarian Forint
            'BGN' => 'лв',   // Bulgarian Lev
            'RON' => 'lei',  // Romanian Leu
            'CZK' => 'Kč',   // Czech Koruna
            'RUP' => '₹',    // Indian Rupee
            'GBP' => '£',    // British Pound
            'JPY' => '¥',    // Japanese Yen
            'CNY' => '¥',    // Chinese Yuan
            'CAD' => 'C$',   // Canadian Dollar
            'AUD' => 'A$',   // Australian Dollar
            'CHF' => 'CHF',  // Swiss Franc
            'SEK' => 'kr',   // Swedish Krona
            'NOK' => 'kr',   // Norwegian Krone
            'DKK' => 'kr',   // Danish Krone
        ];

        foreach ($currencyUpdates as $code => $symbol) {
            DB::table('currencies')
                ->where('code', $code)
                ->update(['symbol' => $symbol]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert currency symbols to original values if needed
        $originalSymbols = [
            'BDT' => '?',
            'EUR' => '?',
            'USD' => '$',
            'PLN' => 'z?',
            'HUF' => 'Ft',
            'BGN' => '??',
            'RON' => 'lei',
            'CZK' => 'K?',
        ];

        foreach ($originalSymbols as $code => $symbol) {
            DB::table('currencies')
                ->where('code', $code)
                ->update(['symbol' => $symbol]);
        }
    }
};
