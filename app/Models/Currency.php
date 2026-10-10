<?php

namespace App\Models;

use App\Traits\HasJsonResourcefulData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Currency
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $symbol
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Currency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency query()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Currency extends BaseModel
{
    use HasFactory, HasJsonResourcefulData;

    protected $table = 'currencies';

    const JSON_API_TYPE = 'currencies';

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'conversion_rate',
    ];

    public static $rules = [
        'name' => 'required|unique:currencies',
        'code' => 'required',
        'symbol' => 'required',
        'conversion_rate' => 'required',
    ];

    public function currencyHistories()
    {
        return $this->hasMany(CurrencyHistory::class, 'currency_id');
    }

    protected static function booted()
    {
        static::saved(function ($currency) {
            if ($currency->conversion_rate !== null) {
                CurrencyHistory::updateOrCreate(
                    [
                        'currency_id' => $currency->id,
                        'date' => now()->toDateString(),
                    ],
                    [
                        'conversion_rate' => (float) $currency->conversion_rate,
                    ]
                );
            }
        });
    }

    /**
     * Get effective conversion rate for a currency code on a specific date.
     *
     * @param string $currencyCode
     * @param string|null $date
     * @return float
     */
    public static function getEffectiveRate(string $currencyCode, ?string $date = null): float
    {
        if (strtoupper($currencyCode) === 'EUR') {
            return 1.0;
        }

        $currency = self::where('code', $currencyCode)->first();
        if (!$currency) {
            return 1.0;
        }

        if ($date) {
            $historyRate = CurrencyHistory::where('currency_id', $currency->id)
                ->where('date', '<=', $date)
                ->orderBy('date', 'desc')
                ->value('conversion_rate');

            if ($historyRate !== null && (float) $historyRate > 0) {
                return (float) $historyRate;
            }
        }

        return (float) ($currency->conversion_rate ?? 1.0);
    }

    /**
     * @return array
     */
    public function prepareLinks(): array
    {
        return [
            'self' => route('currencies.show', $this->id),
        ];
    }

    /**
     * @return array
     */
    public function prepareAttributes(): array
    {
        $fields = [
            'name' => $this->name,
            'code' => $this->code,
            'symbol' => $this->symbol,
            'conversion_rate' => $this->conversion_rate,
        ];

        return $fields;
    }
}
