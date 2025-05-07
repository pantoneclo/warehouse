<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route('sale'); // Get the sale ID from the route

        $rules = Sale::$rules;

        // Modify the unique rule for 'order_no' to ignore the current record
        $rules['order_no'] = 'required|string|unique:sales,order_no,' . $id;

        return $rules;
    }
}
