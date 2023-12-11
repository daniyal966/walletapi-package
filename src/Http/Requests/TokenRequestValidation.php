<?php
namespace WalletApi\Configuration\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class TokenRequestValidation extends FormRequest
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
            'email' => 'required|email|exists:affiliates',
            'password' => 'required',
        ];
    }
    public function messages()
    { 
        return [
            'email.regex' => 'Email must contain only alphabets(a-zA-Z), numbers(0-9), period(.) and underscore(_).',
            'email.required' => 'email is Required',
            'email.exists' => 'email is not registered',
        ];
    }
}