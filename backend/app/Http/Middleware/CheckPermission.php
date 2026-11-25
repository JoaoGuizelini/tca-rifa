<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function __construct(protected PermissionService $service) {}

    public function handle(Request $request, Closure $next)
    {
        $resource = $request->route()?->getName();
        abort_unless($resource && $this->service->isAuthorized($resource, $request->user()), 403, 'Seu perfil não possui essa permissão.');

        return $next($request);
    }
}
