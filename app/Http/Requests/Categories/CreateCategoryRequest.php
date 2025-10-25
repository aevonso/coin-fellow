<?php

namespace App\Http\Requests\Categories;

use App\Http\Requests\BaseRequest;

class CreateCategoryRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7'
        ];
    }
}