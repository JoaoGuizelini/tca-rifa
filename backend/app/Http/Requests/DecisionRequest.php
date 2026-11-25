<?php

namespace App\Http\Requests;

class DecisionRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['approve' => 'required|boolean', 'reason' => 'required|string|max:500'];
    }
}

