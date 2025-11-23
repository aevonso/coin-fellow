<?php

namespace App\Http\Requests\Balances;

use App\Http\Request\BaseRequest;

class SettlementRequest extends BaseRequest {
    public function authorize(): bool 
    {
        return true;
    }

    public function rules(): array {
        return [
            'fromUserId' => 'required|exists:users,id',
            'toUserId' => 'required|exists:users,id',
            'amount'=> 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255'
        ];
    }
}