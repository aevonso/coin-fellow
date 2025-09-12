<?php
namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return[
            'login' => 'required|string',
            'password' => 'required|string'
        ];
    }
}