<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends BaseModel
{
    use HasFactory;
    protected $table = 'addresses';

    protected $fillable = [

        'name',
        'city',
        'contact_email',
        'contact_name',
        'contact_phone',
        'country_iso_code',
        'house_number',
        'street',
        'zip_code',
        'house_number_info',

    ];

    public static $rules = [
        // Validation rules
    ];
}
