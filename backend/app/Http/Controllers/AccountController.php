<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function __construct(protected AccountService $service) {}

    public function csrf()
    {
        return ['token' => csrf_token()];
    }

    public function show(Request $request)
    {
        return $request->user();
    }

    public function store(RegisterRequest $request)
    {
        $user = $this->service->store($request->validated());
        Auth::login($user);
        $request->session()->regenerate();

        return $user;
    }

    public function login(LoginRequest $request)
    {
        $user = $this->service->login($request->validated());
        $request->session()->regenerate();

        return $user;
    }

    public function logout(Request $request)
    {
        $this->service->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }



}
