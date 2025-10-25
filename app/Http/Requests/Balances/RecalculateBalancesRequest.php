<?php

namespace App\Http\Request\Balances;

use App\Http\Request\BaseRequest;

class RecalculateBalancesRequest extends BaseRequest 
{
    public function authorize(): bool 
    {
        return true;
    }

    public function rules(): array {
        return [
            'groupId' => 'required|exists:group, id'
        ];
    }
}