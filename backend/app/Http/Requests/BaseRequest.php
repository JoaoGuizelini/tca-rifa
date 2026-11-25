<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    // Route middleware and resource Policies authorize each protected action.
    public function authorize(): bool
    {
        return true;
    }
}

