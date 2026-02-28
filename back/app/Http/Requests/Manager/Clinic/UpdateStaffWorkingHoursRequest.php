<?php

namespace App\Http\Requests\Manager\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffWorkingHoursRequest  extends FormRequest
{
    public function rules(): array
    {
        return [
            'clinic_id' => 'required|integer|exists:clinics,id',
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|string|in:doctor,secretary',
            'working_hours' => 'required|array|min:1',
            'working_hours.*.day' => 'required|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
            'working_hours.*.start' => 'required|date_format:H:i:s',
            'working_hours.*.end' => 'required|date_format:H:i:s|after:working_hours.*.start',
        ];
    }
}
