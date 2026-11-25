<?php

namespace App\Http\Controllers;

use App\Services\AreaService;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function __construct(protected AreaService $service) {}

    public function index(Request $request)
    {
        return $this->service->mine($request->user()->id);
    }

    public function studio(Request $request)
    {
        return $this->service->studio($request->user()->id);
    }
}
