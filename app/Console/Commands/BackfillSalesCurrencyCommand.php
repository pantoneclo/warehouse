<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\CurrencyHistory;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Console\Command;

class BackfillSalesCurrencyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:backfill-currency {--year= : Specific year to backfill (e.g. 2025 or 2026)} {--force-all : Recalculate all sales with their respective yearly rates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill conversion_rate and selling_value_eur for sales and returns using 2025 and 2026 rates';

    /**
     * Specific conversion rates by year.
     * Base currency is EUR (1.0000). All rates represent value in EUR.
     */
    protected $yearlyRates = [
        2025 => [
            'EUR' => 1.0000,
            'USD' => 0.9200,
            'BDT' => 0.0072,
            'PLN' => 0.2300,
            'HUF' => 0.0025,
            'BGN' => 0.5100,
            'RON' => 0.2000,
            'CZK' => 0.0410,
        ],
        2026 => [
            'EUR' => 1.0000,
            'USD' => 0.9200,
            'BDT' => 0.0072,
            'PLN' => 0.2300,
            'HUF' => 0.0025,
            'BGN' => 0.5100,
            'RON' => 0.2000,
            'CZK' => 0.0410,
        ],
    ];

    /**
     * Country to currency code map fallback.
     */
    protected $countryCurrencyMap = [
        'HU' => 'HUF',
        'PL' => 'PLN',
        'RO' => 'RON',
        'BG' => 'BGN',
        'CZ' => 'CZK',
        'BD' => 'BDT',
        'DE' => 'EUR',
        'SI' => 'EUR',
        'IT' => 'EUR',
        'SK' => 'EUR',
        'GR' => 'EUR',
        'LT' => 'EUR',
        'AT' => 'EUR',
        'ES' => 'EUR',
        'PT' => 'EUR',
        'HR' => 'EUR',
        'EE' => 'EUR',
        'LV' => 'EUR',
        'BE' => 'EUR',
        'NL' => 'EUR',
        'DK' => 'EUR',
        'SE' => 'EUR',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting sales currency backfill with 2025 and 2026 rates...');

        // 1. Sync 2025 and 2026 rates into currency_histories table
        $this->syncYearlyCurrencyHistories();

        $targetYear = $this->option('year');
        $forceAll = $this->option('force-all');

        // 2. Fetch sales to process
        $salesQuery = Sale::query();

        if ($targetYear) {
            $salesQuery->whereYear('date', $targetYear);
            $this->info("Filtering sales for year: {$targetYear}");
        }

        if (!$forceAll) {
            $salesQuery->where(function ($q) {
                $q->whereNull('selling_value_eur')
                    ->orWhere('selling_value_eur', 0)
                    ->orWhereNull('selling_value_usd')
                    ->orWhere('selling_value_usd', 0)
                    ->orWhereNull('conversion_rate')
                    ->orWhere('conversion_rate', 0);
            });
        }

        $salesToFix = $salesQuery->get();
        $this->info("Found {$salesToFix->count()} sales to process.");

        $fixedSalesCount = 0;
        foreach ($salesToFix as $sale) {
            $currencyCode = $sale->currency;
            if (empty($currencyCode)) {
                $countryCode = strtoupper(trim($sale->country ?? ''));
                $currencyCode = $this->countryCurrencyMap[$countryCode] ?? 'EUR';
            }

            $saleDate = $sale->date ? $sale->date->toDateString() : now()->toDateString();
            $rate = $this->getRateForDate($currencyCode, $saleDate);

            $sellingValueEur = round((float) $sale->grand_total * $rate, 4);
            $usdRate = $this->getRateForDate('USD', $saleDate);
            $sellingValueUsd = $usdRate > 0 ? round($sellingValueEur / $usdRate, 4) : 0.0;

            $sale->currency = $currencyCode;
            $sale->conversion_rate = $rate;
            $sale->selling_value_eur = $sellingValueEur;
            $sale->selling_value_usd = $sellingValueUsd;
            $sale->saveQuietly();

            $fixedSalesCount++;
        }

        $this->info("Successfully processed {$fixedSalesCount} sales.");

        // 3. Process sale returns
        $returnsQuery = SaleReturn::with('sale');
        if ($targetYear) {
            $returnsQuery->whereYear('date', $targetYear);
            $this->info("Filtering sale returns for year: {$targetYear}");
        }

        if (!$forceAll) {
            $returnsQuery->where(function ($q) {
                $q->whereNull('selling_value_eur')
                    ->orWhere('selling_value_eur', 0)
                    ->orWhereNull('selling_value_usd')
                    ->orWhere('selling_value_usd', 0)
                    ->orWhereNull('conversion_rate')
                    ->orWhere('conversion_rate', 0);
            });
        }

        $returnsToFix = $returnsQuery->get();
        $this->info("Found {$returnsToFix->count()} sale returns to process.");

        $fixedReturnsCount = 0;
        foreach ($returnsToFix as $return) {
            $currencyCode = $return->currency ?? ($return->sale ? $return->sale->currency : 'EUR');
            $returnDate = $return->date ? $return->date->toDateString() : now()->toDateString();
            $rate = $this->getRateForDate($currencyCode, $returnDate);

            $sellingValueEur = round((float) $return->grand_total * $rate, 4);
            $usdRate = $this->getRateForDate('USD', $returnDate);
            $sellingValueUsd = $usdRate > 0 ? round($sellingValueEur / $usdRate, 4) : 0.0;

            $return->currency = $currencyCode;
            $return->conversion_rate = $rate;
            $return->selling_value_eur = $sellingValueEur;
            $return->selling_value_usd = $sellingValueUsd;
            $return->saveQuietly();

            $fixedReturnsCount++;
        }

        $this->info("Successfully processed {$fixedReturnsCount} sale returns.");
        $this->info('Backfill completed successfully!');

        return 0;
    }

    /**
     * Seed or sync 2025 and 2026 rates into currency_histories.
     */
    protected function syncYearlyCurrencyHistories()
    {
        $currencies = Currency::all()->keyBy('code');

        foreach ($this->yearlyRates as $year => $rates) {
            $date = "{$year}-01-01";
            foreach ($rates as $code => $rate) {
                if (isset($currencies[$code])) {
                    CurrencyHistory::updateOrCreate(
                        [
                            'currency_id' => $currencies[$code]->id,
                            'date' => $date,
                        ],
                        [
                            'conversion_rate' => (float) $rate,
                        ]
                    );
                }
            }
        }

        // Always sync the active rate in currencies table with the latest rate (2026)
        $latestRates = $this->yearlyRates[2026] ?? end($this->yearlyRates);
        foreach ($latestRates as $code => $rate) {
            if (isset($currencies[$code])) {
                $currencies[$code]->update(['conversion_rate' => (float) $rate]);
            }
        }

        $this->info('Yearly currency histories (2025-01-01 & 2026-01-01) and active currency rates successfully synced.');
    }

    /**
     * Get rate for a specific currency and date based on year.
     */
    protected function getRateForDate(string $currencyCode, ?string $date = null): float
    {
        $code = strtoupper(trim($currencyCode));
        if ($code === 'EUR') {
            return 1.0;
        }

        $year = $date ? (int) substr($date, 0, 4) : (int) date('Y');
        $rateYear = ($year <= 2025) ? 2025 : 2026;

        if (isset($this->yearlyRates[$rateYear][$code])) {
            return (float) $this->yearlyRates[$rateYear][$code];
        }

        return Currency::getEffectiveRate($code, $date);
    }
}
