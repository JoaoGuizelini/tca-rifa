<?php

namespace App\Http\Requests;

class OrganizerRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['public_name' => 'required|string|max:100', 'bio' => 'required|string|max:2000', 'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120'];
    }
}

