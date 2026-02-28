<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class NumberLoginRequest extends FormRequest
{

    public function rules()
    {
        return [
            'number' => ['required', 'string', 'exists:users,number'],
            'password' => ['required', 'string', 'min:8'],
            'fcm_token' => ['nullable', 'string'],
        ];
    }
}
