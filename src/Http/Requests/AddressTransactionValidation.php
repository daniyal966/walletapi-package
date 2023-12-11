<?php

namespace WalletApi\Configuration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressTransactionValidation extends FormRequest
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
        return [
            'address' => 'required',
        ];
    }
    public function messages()
    { 
        return [
            'address.required' => 'address is required',
      
        ];
    }
}
