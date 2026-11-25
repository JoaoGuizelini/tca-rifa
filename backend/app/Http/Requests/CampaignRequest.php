<?php

namespace App\Http\Requests;

class CampaignRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100', 'description' => 'required|string|max:4000', 'prize' => 'required|string|max:200',
            'rules' => 'required|string|max:5000', 'quantity' => 'required|integer|between:1,2000', 'price' => 'required|integer|between:1,1000000',
            'starts' => 'required|date_format:Y-m-d', 'ends' => 'required|date_format:Y-m-d|after_or_equal:starts|after_or_equal:today',
            'category_id' => 'required|integer|exists:categories,id', 'photos' => 'sometimes|array|max:1',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }
}

