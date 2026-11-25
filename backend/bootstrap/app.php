<?php

use App\Http\Middleware\Access;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\PrivateResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php')
    ->withMiddleware(function (Middleware $m) {
        $m->redirectGuestsTo(fn () => '/#/login');
        $m->alias(['access' => Access::class, 'permission' => CheckPermission::class]);
        $m->web(append: [PrivateResponse::class]);
    })
    ->withExceptions(fn (Exceptions $e) => $e->shouldRenderJsonWhen(fn () => true))->create();
