<?php

namespace App\Http\Requests\Manager\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class AddStaffRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email_or_phone' => ['required', 'string'],
            'role' => ['required', 'in:doctor,secretary'],
            'clinic_id' => ['required', 'exists:clinics,id'],
            'working_hours' => ['required', 'array', 'min:1'],
            'working_hours.*.day' => ['required', 'string', 'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'],
            'working_hours.*.start' => ['required', 'date_format:H:i:s'],
            'working_hours.*.end' => ['required', 'date_format:H:i:s', 'after:working_hours.*.start'],
        ];
    }
}
