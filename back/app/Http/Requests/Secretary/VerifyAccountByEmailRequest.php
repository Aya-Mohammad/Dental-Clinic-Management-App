<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAccountByEmailRequest extends FormRequest
{
    

    public function rules()
    {
        return [
            'email' => ['required','email', 'exists:users,email'],
            'otp' => ['required', 'string', 'min:6', 'max:6']
        ];
    }
}
