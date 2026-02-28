<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class AddPatientDataRequest extends FormRequest
{
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'city' => ['sometimes', 'string'],
            'street' => ['sometimes', 'string'],
            'gender' => ['required', 'string'],
            'date_of_birth' => ['required', 'date'],
            'blood_type' => ['required', 'string']
        ];
    }
}
