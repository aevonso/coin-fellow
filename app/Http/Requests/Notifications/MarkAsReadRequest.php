<?php

namespace App\Http\Requests\Notifications;

use App\Http\Requests\BaseRequest;

class MarkAsReadRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notification_ids' => 'sometimes|array',
            'notification_ids.*' => 'exists:notifications,id',
        ];
    }
}