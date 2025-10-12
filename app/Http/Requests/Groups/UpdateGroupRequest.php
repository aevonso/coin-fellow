<?php

namespace App\Http\Requests\Groups;

use App\Http\Requests\BaseRequest;

class UpdateGroupRequest extends BaseRequest {
public function authorize(): bool {
    return true;
}

public function rules(): array {
    return [
        'name' => 'sometimes|string|max:255',
        'currency' => 'sometimes|string|size:3',
        'description' => 'nullable|string|max:500',
    ];
}
}
