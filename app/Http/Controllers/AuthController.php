<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function show()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate($credentials)) {
            return back()->withErrors(['username' => 'Username atau password salah.'])->onlyInput('username');
        }

        $user = Auth::getProvider()->retrieveByCredentials($credentials);
        $cookie = null;

        if (! $user->is_admin) {
            $cookieToken = (string) $request->cookie('device');
            if ($user->device_token && ! hash_equals($user->device_token, $cookieToken)) {
                return back()->withErrors(['username' => 'Akun ini sudah dipakai di perangkat lain. Hubungi pengawas.'])->onlyInput('username');
            }
            if (! $user->device_token) {
                $user->forceFill(['device_token' => Str::random(40)])->save();
            }
            $cookie = cookie()->forever('device', $user->device_token);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $response = redirect()->route('home');

        return $cookie ? $response->withCookie($cookie) : $response;
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
