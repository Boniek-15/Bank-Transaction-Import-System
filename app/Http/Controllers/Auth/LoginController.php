<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    function showLoginForm(){
        return Inertia::render('auth/Login');
    }

    function login(Request $request){
        $validated = request()->validate([
            'username' => 'string|required',
            'password' => 'string|required',
        ]);
        $authenticated = Auth::attempt([
            'username' => $request->username,
            'password' => $request->password
        ]);
        if($authenticated)
        {
            $request->session()->regenerate();
            return redirect()->route('Imports');
        }
        else
        {
            return back()->withErrors([
                'username' => 'Invalid username or password',
            ]);
        }
    }

    function logout(Request $request){
       Auth::logout();
       $request->session()->invalidate();
       $request->session()->regenerateToken();
       return redirect('/login');
    }
}
