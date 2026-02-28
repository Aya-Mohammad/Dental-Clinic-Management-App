<?php

namespace App\Http\Requests\Manager\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class StoreClinicRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'bio' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'unique:clinics,phone'],
            'city_name' => ['required', 'string'],
            'street_name' => ['required', 'string'],
            'stripe_token' => ['required', 'string'],
        ];
    }
}
