<?php

namespace App\Http\Requests\Manager\Advertisment;

use Illuminate\Foundation\Http\FormRequest;

class ShowAdvertismentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'advertisment_id' => ['required', 'integer', 'exists:advertisments,id'],
        ];
    }
}
