<?php

namespace App\Http\Requests\Manager\Service;
use Illuminate\Foundation\Http\FormRequest;

class RemoveServiceFromClinicRequest extends FormRequest
{
    public function rules()
    {
        return [
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'service_id' => ['required', 'integer', 'exists:services,id']
        ];
    }
}
