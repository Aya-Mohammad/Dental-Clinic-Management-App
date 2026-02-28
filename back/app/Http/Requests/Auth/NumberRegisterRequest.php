<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class NumberRegisterRequest extends FormRequest
{

    public function rules()
    {
        return [
            'number' => ['required', 'string', 'unique:users,number'],
            'name' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
            'fcm_token' => ['required', 'string']
        ];
    }
}
