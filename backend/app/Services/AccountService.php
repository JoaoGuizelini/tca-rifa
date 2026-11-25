<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function __construct(protected UserRepository $repository) {}

    public function store(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->repository->store(Arr::only($data, ['name', 'email', 'cpf', 'phone', 'password']));

            return $user->fresh();
        });
    }

    public function login(array $data): User
    {
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'suspended' => false])) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas ou conta suspensa.']);
        }
        $user = Auth::user();

        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
    }



}
