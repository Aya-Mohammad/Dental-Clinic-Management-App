<?php

namespace App\Http\Requests\Manager\Advertisment;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAdvertismentImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image_id' => 'required|exists:advertisment_images,id',
        ];
    }
}
