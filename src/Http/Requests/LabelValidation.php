<?php

namespace WalletApi\Configuration\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

class LabelValidation extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'label' => 'required|max:30|min:3',
            'altcoin' => 'required|in:BCH,BTC,LTC,ETH,SOL',
            'address' => 'required'
            // 'address' => 'required|exists:addresses,address,address_type,'.$request->altcoin
        ];
    }
    public function messages()
    {
        return [
            'label' => 'Label can be of minimum 3 and maximum 30 characters',
            'altcoin.in' => 'Only LTC,BTC,BCH accepted',
            'address.exists' => 'Invalid Address',
        ];
    }
}
