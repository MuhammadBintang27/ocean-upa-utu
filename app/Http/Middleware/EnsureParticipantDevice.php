<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** A participant account stays bound to the browser it first logged in from. */
class EnsureParticipantDevice
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user->device_token || ! hash_equals($user->device_token, (string) $request->cookie('device'))) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['username' => 'Akun ini terkunci ke perangkat lain. Hubungi pengawas.']);
        }

        return $next($request);
    }
}
