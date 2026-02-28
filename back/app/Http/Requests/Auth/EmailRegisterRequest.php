<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class EmailRegisterRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
            'fcm_token' => ['required', 'string']
        ];
    }
}
