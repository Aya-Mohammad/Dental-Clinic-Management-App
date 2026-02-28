<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTreatmentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'treatment_id' => ['required', 'exists:treatments,id'],
            'service_number' => ['required'],
            'stage_number' => ['required'],
            'status' => ['required']
        ];
    }
}
