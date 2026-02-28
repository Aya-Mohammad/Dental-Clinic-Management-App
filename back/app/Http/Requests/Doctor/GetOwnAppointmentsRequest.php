<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class GetOwnAppointmentsRequest extends FormRequest
{
    public function rules()
    {
        return [
            'clinic_id' => ['required', 'exists:clinics,id'],
            'date' => ['required', 'date']
        ];
    }
}
