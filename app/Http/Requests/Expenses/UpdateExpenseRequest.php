<?php

namespace App\Http\Request\Expenses;

use App\Http\Request\BaseRequest;

class UpdateExpenseRequest extends BaseRequest 
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'date' => 'sometimes|date',
            'categoryId' => 'nullable|exists:categories,id',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:users,id',
        ];
    }

}