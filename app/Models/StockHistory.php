<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StockHistory
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int $product_id
 * @property float $quantity
 * @property float $old_quantity
 * @property float $new_quantity
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $action
 * @property int|null $user_id
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Warehouse $warehouse
 * @property-read \App\Models\User|null $user
 */
class StockHistory extends Model
{
    use HasFactory;

    protected $table = 'stock_histories';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'old_quantity',
        'new_quantity',
        'reference_type',
        'reference_id',
        'action',
        'user_id',
        'note',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'double',
        'old_quantity' => 'double',
        'new_quantity' => 'double',
        'reference_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
