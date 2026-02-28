<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class AddSelfDataRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'city' => ['sometimes', 'string'],
            'street' => ['sometimes', 'string'],
            'gender' => ['required', 'string'],
            'date_of_birth' => ['required', 'date'],
            'blood_type' => ['required', 'string']
        ];
    }
}
