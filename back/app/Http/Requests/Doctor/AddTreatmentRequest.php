<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class AddTreatmentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'number' => ['required_without:email', 'exists:users,number'],
            'email' => ['required_without:number', 'exists:users,email'],
            'role' => ['required', 'in:D,P,S'],
            'clinic_id' => ['required', 'exists:clinics,id'],
            'services' => ['required', 'array'],
            'services.*.service_id' => ['required', 'exists:services,id'],
            'services.*.order' => ['required', 'integer'],
            'teeth' => ['sometimes', 'array'],
            'teeth.*' => ['integer']
        ];
    }
}
