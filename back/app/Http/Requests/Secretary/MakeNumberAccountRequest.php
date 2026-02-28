<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class MakeNumberAccountRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'number' => ['required', 'string', 'unique:users,number'],
            'name' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
