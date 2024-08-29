<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends BaseModel
{
    use HasFactory;
    protected $table = 'shipments';
    protected $fillable = [
        
        'sale_id',
        'pickup_address_id',
        'delivery_address_id',
        'parcel_company_id',
        'parcel_id',
        'parcel_number',
        'cod_amount',
        'cod_reference',
        'client_reference',
        'count',
        'content',
        'pickup_date',
        'status_description',
        'depot_city',
        'status_date',
        

    ];

    public static $rules = [
        
        'sale_id' => 'required|exists:sales,id', 
        // 'parcel_status' => 'string|required',
        // 'parcel_number'=>'string|required',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
    public function pickupAdress()
    {
        return $this->belongsTo(Adress::class);
    }
    public function deliveryAdress()
    {
        return $this->belongsTo(Adress::class);
    }
}
