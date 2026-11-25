<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecisionRequest;
use App\Http\Requests\OrganizerRequest;
use App\Services\OrganizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrganizerController extends Controller
{
    public function __construct(protected OrganizerService $service) {}

    public function show(int $id)
    {
        return $this->service->find($id);
    }

    public function store(OrganizerRequest $request)
    {
        $this->service->store($request->validated(), $request->user());

        return response()->noContent();
    }

    public function decide(DecisionRequest $request, int $id)
    {
        return $this->service->decide($id, $request->validated(), $request->user());
    }

    public function document(Request $request, int $id)
    {
        $path = $this->service->document($id, $request->user());

        return Storage::disk('local')->download($path, 'identidade.'.pathinfo($path, PATHINFO_EXTENSION),
            ['Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
