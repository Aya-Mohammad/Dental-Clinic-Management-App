<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableStagesForPatientRequest extends FormRequest
{
    
    public function rules()
    {
        return [
            'number' => ['required_without:email', 'exists:users,number'],
            'email' => ['required_without:number', 'exists:users,email'],
            'clinic_id' => ['required', 'exists:clinics,id']
        ];
    }
}
