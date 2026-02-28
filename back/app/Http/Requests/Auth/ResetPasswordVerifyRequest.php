<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordVerifyRequest extends FormRequest
{

    public function rules()
    {
        return [
            'otp' => ['required', 'string', 'min:6', 'max:6'],
            'new_password' => ['required', 'string', 'min:8']
        ];
    }
}
