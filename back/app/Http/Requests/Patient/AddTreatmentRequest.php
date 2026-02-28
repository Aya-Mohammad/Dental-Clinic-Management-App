<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class AddTreatmentRequest extends FormRequest
{

    public function rules()
    {
        return [
            'clinic_id' => ['required', 'exists:clinics,id'],
            'role' => ['required', 'in:D,P,S'],
            'services' => ['required', 'array'],
            'services.*.service_id' => ['required', 'exists:services,id'],
            'services.*.order' => ['required', 'integer'],
        ];
    }
}
