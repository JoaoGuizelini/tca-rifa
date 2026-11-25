<?php

namespace App\Http\Requests;

class ReservationRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['numbers' => 'required|array|min:1|max:50', 'numbers.*' => 'required|integer|min:1|distinct'];
    }
}

