<?php

namespace WalletApi\Configuration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressValidation extends FormRequest
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
            'altcoin' => 'required|in:LTC,BTC,BCH,ETH,SOL',
            'address' => 'required'
        ];
    }
    public function messages()
    { 
        return [
            'altcoin.in' => 'Only LTC,BCH,BTC accepted',
        ];
    }
}
