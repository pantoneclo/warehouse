<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Package;

class UpdatePackageRequest extends FormRequest
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
        $rules = Package::$rules;
        $rules['code'] = 'required|unique:packages,code,'.$this->route('package');

        return $rules;
    }
    public function messages()
    {
        return [
            'code.unique' => __('messages.error.code_taken'),
        ];
    }
}
