<?php

namespace App\Http\Requests\Budgets;

use App\Http\Requests\BaseRequest;

class UpdateBudgetRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'sometimes|numeric|min:0.01',
            'period' => 'sometimes|in:daily,weekly,monthly,yearly',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'sometimes|boolean',
            'notify_on_percentage' => 'nullable|integer|min:1|max:100',
            'currency' => 'nullable|string|size:3'
        ];
    }
}