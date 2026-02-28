<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class GetStageAvailableDatesRequest extends FormRequest
{
    
    public function rules()
    {
        return [
            'stage_id' => ['required', 'exists:stages,id'],
            'clinic_id' => ['required', 'exists:clinics,id'],
            'duration' => ['sometimes', 'date_format:H:i:s'],
        ];
    }
}
