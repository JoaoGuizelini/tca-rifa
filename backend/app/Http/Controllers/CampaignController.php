<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignRequest;
use App\Http\Requests\CampaignStatusRequest;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CampaignController extends Controller
{
    public function __construct(protected CampaignService $service) {}

    public function index(Request $request)
    {
        return $this->service->all($request->only(['q', 'category', 'sort']));
    }

    public function show(Request $request, int $id)
    {
        return $this->service->detail($id, $request->user());
    }

    public function store(CampaignRequest $request)
    {
        return $this->service->store($request->validated(), $request->user());
    }

    public function update(CampaignRequest $request, int $id)
    {
        return $this->service->update($request->validated(), $id, $request->user());
    }

    public function change(CampaignStatusRequest $request, int $id)
    {
        return $this->service->change($id, $request->validated(), $request->user());
    }

    public function photo(Request $request, string $name)
    {
        return Storage::disk('local')->response($this->service->photo($name, $request->user()));
    }
}
