<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'date' => ['required', 'date'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'clinic_id' => ['required', 'exists:clinics,id'],
            'time' => ['required', 'date_format:H:i:s'],
            'treatment_id' => ['required_without:service_id', 'exists:treatments,id'],
            'service_id' => ['required_without:treatment_id', 'exists:services,id'],
            'stage_id' => ['required', 'exists:stages,id']
        ];
    }
}
