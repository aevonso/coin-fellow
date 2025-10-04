<?php

namespace App\Http\Requests\Groups;

use App\Http\Requests\BaseRequest;

class InviteUserRequest extends BaseRequest 
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'email_or_username' => 'required|string',
            'role' => 'sometimes|string|in:member,admin',
        ];
    }
}
