<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class TelegramAuthRequest extends BaseRequest {

    public function authorize():bool {
        return true;
    }

    public function rules(): array {
        return [
            'telegram_user_id' => 'required|string',
            'username' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'language_code' => 'nullable|string',
            'avatar_url' => 'nullable|url',
        ];
    }
}