<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return back()->withErrors(['username' => '用戶名或密碼錯誤']);
        }

        if (!$user->verifyPassword($request->password)) {
            return back()->withErrors(['username' => '用戶名或密碼錯誤']);
        }

        Auth::login($user, $request->boolean('remember'));

        $user->update(['last_login_at' => now()]);

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();
        $user->password = bcrypt($request->new_password);
        $user->save();

        return back()->with('success', '密碼已更新');
    }
}
