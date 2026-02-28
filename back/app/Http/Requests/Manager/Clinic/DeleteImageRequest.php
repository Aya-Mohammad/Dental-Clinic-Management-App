<?php

namespace App\Http\Requests\Manager\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class DeleteImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image_id' => ['required','exists:clinic_images,id'],
        ];
    }
}

