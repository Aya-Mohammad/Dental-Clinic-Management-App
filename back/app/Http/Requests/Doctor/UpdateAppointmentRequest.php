<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'appointment_id' => ['required', 'exists:appointments,id'],
            'status' => ['required', 'in:C,X']
        ];
    }
}
