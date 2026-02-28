<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class GetClinicAppointmentsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'clinic_id' => ['required', 'exists:clinics,id'],
            'date' => ['required', 'date'],
            'role' => ['required', 'in:M,S']
        ];
    }
}
