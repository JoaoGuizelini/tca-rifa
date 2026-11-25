<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecisionRequest;
use App\Http\Requests\SuspensionRequest;
use App\Services\AdminService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(protected AdminService $service) {}

    public function index(Request $request)
    {
        return $this->service->all($request->user());
    }

    public function campaign(DecisionRequest $request, int $id)
    {
        return $this->service->campaign($id, $request->validated(), $request->user());
    }

    public function suspend(SuspensionRequest $request, int $id)
    {
        $this->service->suspend($id, $request->boolean('suspended'), $request->user());

        return response()->noContent();
    }

}
