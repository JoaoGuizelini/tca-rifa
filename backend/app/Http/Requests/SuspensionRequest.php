<?php

namespace App\Http\Requests;

class SuspensionRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['suspended' => 'required|boolean'];
    }
}

