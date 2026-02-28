<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class ShowPatientRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id' => ['required', 'exists:users,id']
        ];
    }
}
