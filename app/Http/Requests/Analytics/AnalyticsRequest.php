<?php

namespace App\Http\Requests\Analytics;

use App\Http\Requests\BaseRequest;

class AnalyticsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'period' => 'nullable|in:daily,weekly,monthly,yearly',
            'limit' => 'nullable|integer|min:1|max:50'
        ];
    }
}