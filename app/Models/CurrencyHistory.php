<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyHistory extends Model
{
    use HasFactory;

    protected $table = 'currency_histories';

    protected $fillable = [
        'currency_id',
        'conversion_rate',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'conversion_rate' => 'double',
        'currency_id' => 'integer',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
