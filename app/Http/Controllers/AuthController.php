<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
    $request->session()->regenerate();

    $redirect = $request->get('redirect');

    if (!empty($redirect)) {
        // decode dulu biar tidak rusak
        return redirect(urldecode($redirect));
    }

    return redirect('/dashboard');
}


        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
