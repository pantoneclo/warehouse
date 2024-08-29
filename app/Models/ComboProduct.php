<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComboProduct extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'combo_id',
        'product_id',
        'code'
    ];

    public function combo()
    {
        return $this->belongsTo(Combo::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

}
