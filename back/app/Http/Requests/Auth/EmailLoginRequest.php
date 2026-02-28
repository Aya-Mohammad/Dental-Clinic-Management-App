<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class EmailLoginRequest extends FormRequest
{

    public function rules()
    {
        return [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'fcm_token' => ['nullable', 'string'],
        ];
    }

}
