<?php

namespace App\Http\Requests\Budgets;

use App\Http\Requests\BaseRequest;

class CreateBudgetRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:categories,id',
            'user_id' => 'nullable|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'period' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notify_on_percentage' => 'nullable|integer|min:1|max:100',
            'currency' => 'nullable|string|size:3'
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Сумма бюджета обязательна',
            'amount.min' => 'Сумма бюджета должна быть больше 0',
            'period.required' => 'Период бюджета обязателен',
            'period.in' => 'Неверный период бюджета',
            'start_date.required' => 'Дата начала обязательна',
            'start_date.date' => 'Неверный формат даты начала',
            'end_date.date' => 'Неверный формат даты окончания',
            'end_date.after' => 'Дата окончания должна быть после даты начала'
        ];
    }
}