<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = (string) $value;
        if (! preg_match('/^[0-9]{11}$/', $cpf) || count(array_unique(str_split($cpf))) === 1) {
            $fail('Informe um CPF válido.');
            return;
        }
        for ($i = 9; $i <= 10; $i++) {
            $sum = 0;
            for ($j = 0; $j < $i; $j++) $sum += (int) $cpf[$j] * ($i + 1 - $j);
            if (((10 * $sum) % 11) % 10 !== (int) $cpf[$i]) {
                $fail('Informe um CPF válido.');
                return;
            }
        }
    }
}

