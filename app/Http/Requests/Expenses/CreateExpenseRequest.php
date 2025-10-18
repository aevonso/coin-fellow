<?php

namespace App\Http\Request\Expenses;

use App\Http\Request\BaseRequest;

class CreateExpensesRequest extends BaseRequest 
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'groupId' => 'required|exists|group,id',
            'categoryId' => 'nullable|exists:categories,id',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:users,id',
        ];
    }
}