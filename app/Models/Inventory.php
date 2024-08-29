<?php

namespace App\Models;

use App\Models\Contracts\JsonResourceful;
use App\Traits\HasJsonResourcefulData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Inventory extends BaseModel implements JsonResourceful
{
    use HasFactory, HasJsonResourcefulData;

    protected $table = 'inventories';
    protected $fillable = [
        'product_id', 'is_combo','sticker_meas_unit', 'no_of_items_per_box', 'no_of_boxes','net_wt','gross_wt','carton_meas','insert_key','created_by'
    ];

    const JSON_API_TYPE = 'inventories';


    public function prepareLinks(): array
    {
        return [
            'self' => route('inventory.show', $this->id),
        ];
    }

    public function prepareAttributes(): array
    {
        $fields = [
            'no_of_items_per_box' => $this->no_of_items_per_box,
            'no_of_boxes' => $this->no_of_boxes,
            'is_combo' => $this->is_combo,
            'insert_key' => $this->insert_key,
            'net_wt' => $this->net_wt,
            'gross_wt' => $this->gross_wt,
            'carton_meas' => $this->carton_meas,
            'products' => $this->combo->map(function ($item) {
                return $item->product;
            }),
            'created_at' => $this->created_at->toDateTimeString(),
        ];

        return $fields;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function combo()
    {
        return $this->hasMany(InventoryCombo::class, 'inventory_id', 'id');
    }

    public function getCreatedAtAttribute($date)
    {
        try {
            $parsedDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date);
            return $parsedDate->format('Y-m-d');
        } catch (\Carbon\Exceptions\InvalidFormatException $e) {
            Log::error('Invalid date format: ' . $e->getMessage(), ['date' => $date]);
            // Return the original date or a default value
            return $date;
        }
    }

}
