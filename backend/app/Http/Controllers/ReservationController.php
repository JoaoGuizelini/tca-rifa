<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationRequest;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct(protected ReservationService $service) {}

    public function store(ReservationRequest $request, int $id)
    {
        return ['id' => $this->service->store($id, array_map('intval', $request->validated('numbers')), $request->user())];
    }

    public function confirm(Request $request, int $id)
    {
        $this->service->confirm($id, $request->user());

        return response()->noContent();
    }
}
