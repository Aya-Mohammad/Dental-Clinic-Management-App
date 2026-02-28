<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SetSubscriptionValuesRequest extends FormRequest
{
     public function rules()
    {
        return [
            'price' => ['required', 'numeric', 'min:0'],
            'days'  => ['required', 'integer', 'min:1'],
        ];
    }
}
