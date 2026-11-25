<?php

namespace App\Http\Requests;

class LoginRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return ['email' => 'required|email', 'password' => 'required|string'];
    }
}

