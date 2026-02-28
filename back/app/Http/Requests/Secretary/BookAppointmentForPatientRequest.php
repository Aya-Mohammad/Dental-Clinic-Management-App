<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentForPatientRequest extends FormRequest
{

    public function rules()
    {
        return [
            'number' => ['required_without:email', 'exists:users,number'],
            'email' => ['required_without:number', 'exists:users,email'],
            'date' => ['required', 'date'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'clinic_id' => ['required', 'exists:clinics,id'],
            'time' => ['required', 'date_format:H:i:s'],
            'treatment_id' => ['required_without:service_id', 'exists:treatments,id'],
            'service_id' => ['required_without:treatment_id', 'exists:services,id'],
            'stage_id' => ['required', 'exists:stages,id'],
            'duration' => ['sometimes', 'date_format:H:i:s']
        ];
    }
}
