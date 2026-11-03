<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegisterController extends Controller
{
    function showRegisterForm(){
        return Inertia::render('auth/Register');
    }

    function register(Request $request){
        $validated = request()->validate([
            'username' => 'string|required|unique:users',
            'password' => 'string|required|min:8|confirmed',
            'password_confirmation' => 'string|required',
        ]);
        $user = User::create([
            'username' => $validated['username'],
            'password' => $validated['password'],
        ]);
        auth::login($user);
        return redirect()->route('Imports');
    }
}
