<?php

namespace App\Http\Requests\Manager\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class GetImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
        ];
    }
}
