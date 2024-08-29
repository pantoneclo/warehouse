<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = ['type','ref_id'];

    public static $rules = [
        'type' => 'required|string',
        'ref_id' => 'required|numeric'
    ];

    protected $allowedTypes = ['product','package' ,'ajdustment','quotation','purchase','sale','transfer','expense','people','warehouse','reports','settings'];

    public function setTypeAttribute($value)
    {
        // Check if the provided value is in the list of allowed statuses
        if (in_array($value, $this->allowedTypes)) {
            $this->attributes['type'] = $value;
        } else {
            // Set a default value or handle the validation error as needed
            $this->attributes['type'] = 'error'; // Change to your desired default value
            // You can also throw an exception or log an error here
        }
    }
}
