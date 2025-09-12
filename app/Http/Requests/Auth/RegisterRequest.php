<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BaseRequest {
    public function authorize(): bool {
        return true;
    }
    
 public function rules(): array
    {
        return [
            'email' => 'sometimes|required_without:phone|email|unique:users,email',
            'phone' => 'sometimes|required_without:email|string|unique:users,phone',
            'password' => ['required', 'confirmed', Password::min(8)],
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
        ];
    }

    public function messages():array {
        return[
            'email.required_without' => 'Email или телефон обязателен'
        ];
    }
}