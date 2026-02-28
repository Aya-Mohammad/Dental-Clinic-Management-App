<?php

namespace App\Http\Requests\Manager\Service;

use Illuminate\Foundation\Http\FormRequest;

class AddServiceRequest extends FormRequest
{
    public function rules()
    {
        return [
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'name' => ['required_without:service_id', 'string'],
            'description' => ['nullable', 'string'],
            'duration' => ['nullable', 'string'],
            'stages_number' => ['nullable', 'integer'],
            'price' => ['required', 'min:0'],
            'accessibility' => ['required', 'in:A,D'],
            'stages' => ['nullable', 'array'],
            'stages.*.duration' => ['required_with:stages', 'date_format:H:i:s'],
            'stages.*.title' => ['required_with:stages', 'string'],
            'stages.*.specialization' => ['required_with:stages', 'in:C,G,E,O'],
            'stages.*.description' => ['required_with:stages', 'string'],
        ];
    }
}
