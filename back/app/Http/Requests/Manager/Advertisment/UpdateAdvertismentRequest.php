<?php

namespace App\Http\Requests\Manager\Advertisment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvertismentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'advertisement_id' => 'required|exists:advertisments,id',
            'title'            => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'images'           => 'nullable|array',
            'images.*'         => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
}
