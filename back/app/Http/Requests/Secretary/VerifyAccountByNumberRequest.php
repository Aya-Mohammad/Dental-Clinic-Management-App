<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAccountByNumberRequest extends FormRequest
{
    
    public function rules()
    {
        return [
            'number' => ['required', 'exists:users,number'],
            'otp' => ['required', 'string', 'min:6', 'max:6']
        ];
    }
}
