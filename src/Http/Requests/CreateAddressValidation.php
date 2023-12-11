<?php

namespace WalletApi\Configuration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class CreateAddressValidation extends FormRequest
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
            'request_id' => 'bail|required|int|digits_between:1,11',
            'email' => [
                'required',
                'not_regex:/['.self::unichr(0x1F300).'-'.self::unichr(0x1F5FF).self::unichr(0xE000).'-'.self::unichr(0xF8FF).']/',
                'email',
                'max:128'
            ]
        ];
    }
    public function messages()
    { 
        return [
            'email.not_regex' => 'Email must contain only alphabets(a-zA-Z), numbers(0-9), period(.) and underscore(_).',
            'email.required' => 'Email is required',
            'altcoin.in' => 'Only LTC, BTC, BCH, ETH Accepted',
            'source.in' => 'Only PURSER, DEFAULT, QB Allowed',
        ];
    }

    public static function unichr($i) {
        return iconv('UCS-4LE', 'UTF-8', pack('V', $i));
    }
}
