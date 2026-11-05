<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'participant' => \App\Http\Middleware\EnsureParticipantDevice::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The host caps concurrent MySQL connections (~20). When a burst hits that cap, ask the browser to retry
        // instead of showing a 500: pages reload themselves, JSON callers (autosave) already retry.
        $busy = fn (Throwable $e) => $e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), '[2002]');
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) use ($busy) {
            if (! $busy($e)) {
                return null;
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Server sedang ramai, coba lagi.'], 503, ['Retry-After' => '2']);
            }

            return response()->view('errors.busy', ['retry' => $request->isMethod('GET') ? $request->fullUrl() : url('/')], 503, ['Retry-After' => '3']);
        });
    })->create();
