<?php

namespace App\Models;

use App\Models\Contracts\JsonResourceful;
use App\Traits\HasJsonResourcefulData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCombo extends BaseModel implements JsonResourceful
{
    use HasFactory, HasJsonResourcefulData;

    protected $table = 'inventory_combos';

    protected $fillable = [
        'inventory_id', 'product_id', 'sticker_id', 'barcode_image', 'item_per_box','variant_id','size','color','style'
    ];

    const JSON_API_TYPE = 'inventories';

    public function prepareLinks(): array
    {
        return [
            'self' => route('inventory-combo.show', $this->id),
        ];
    }

    public function prepareAttributes(): array
    {
        $fields = [
            'inventory_id' => $this->inventory_id,
            'product_id' => $this->product_id,
            'item_per_box' => $this->item_per_box,
            'variant_id' => $this->variant_id,
            'color' => $this->color,
            'size' => $this->size,
        ];

        return $fields;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
