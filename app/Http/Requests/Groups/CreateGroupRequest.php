<?php
namespace App\Http\Requests\Groups;

use App\Http\Requests\BaseRequest;

class CreateGroupRequest extends BaseRequest {
    public function authorize (): bool {
        return true;
    }

    public function rules(): array {
        return [
            'name' => 'required|string|max:255',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string|max:500',
        ];
    }
}