<?php

namespace App\Http\Requests;

use App\Models\Variant;
use Illuminate\Foundation\Http\FormRequest;

class CreateVariantRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        $input = request()->input();

        if (\isBulkRequest($input)) {
            // $inputData is a plain numeric array
            // Perform actions accordingly
            return Variant::$rulesBulk;
        } else {
            // $inputData is not a plain numeric array
            // Perform actions accordingly
            return Variant::$rules;
        }
    }

    // public function messages()
    // {
    //     return [
    //         'code.unique' => __('messages.error.code_taken'),
    //     ];

    // }
}
