<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'specialization' => ['required', 'in:C,G,E,O'],
            'experience_years' => ['required', 'integer'],
            'bio' => ['required', 'string'],
            'phone' => ['required']
        ];
    }
}
