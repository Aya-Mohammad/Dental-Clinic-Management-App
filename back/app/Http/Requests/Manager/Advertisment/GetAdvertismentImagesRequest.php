<?php

namespace App\Http\Requests\Manager\Advertisment;

use Illuminate\Foundation\Http\FormRequest;

class GetAdvertismentImagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'advertisment_id' => 'required|exists:advertisments,id',
        ];
    }
}
