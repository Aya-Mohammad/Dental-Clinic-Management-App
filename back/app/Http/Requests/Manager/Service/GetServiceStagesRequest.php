<?php

namespace App\Http\Requests\Manager\Service;

use Illuminate\Foundation\Http\FormRequest;

class GetServiceStagesRequest extends FormRequest
{
    public function rules()
    {
        return [
             'service_id' => ['required', 'integer', 'exists:services,id']
        ];
    }
}
