<?php

namespace App\Http\Middleware;

use Closure;

class Access
{
    public function handle($r, Closure $next, ...$roles)
    {
        abort_unless($r->user(), 401, 'Entre para continuar.');
        abort_if($r->user()->suspended, 403, 'Conta suspensa.');
        abort_if($roles && ! $r->user()->hasRole(...$roles), 403, 'Seu perfil não possui essa permissão.');

        return $next($r);
    }
}
