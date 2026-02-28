<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class ShowToothTreatmentsRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'clinic_id' => ['required', 'exists:clinics,id'],
            'tooth_number' => ['required', 'integer']
        ];
    }
}
