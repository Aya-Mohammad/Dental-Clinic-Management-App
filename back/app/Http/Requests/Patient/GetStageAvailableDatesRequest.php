<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class getStageAvailableDatesRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'stage_id' => ['required', 'exists:stages,id'],
            'clinic_id' => ['required', 'exists:clinics,id']
        ];
    }
}
