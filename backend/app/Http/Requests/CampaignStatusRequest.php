<?php

namespace App\Http\Requests;

class CampaignStatusRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['action' => 'required|in:enviar,cancelar,resultado', 'winner' => 'required_if:action,resultado|nullable|integer|min:1'];
    }
}

