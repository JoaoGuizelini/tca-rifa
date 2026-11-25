<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use Illuminate\Validation\Rule;

class RegisterRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'phone' => preg_replace('/\D/', '', (string) $this->input('phone')),
        ]);
    }

    protected function details(?int $userId = null): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:200', Rule::unique('users')->ignore($userId)],
            'phone' => 'required|digits_between:10,11',
            'cpf' => ['bail', 'required', 'digits:11', new Cpf, Rule::unique('users')->ignore($userId)],
        ];
    }

    public function rules(): array
    {
        return $this->details() + ['password' => 'required|string|min:10|max:72|confirmed'];
    }
}

