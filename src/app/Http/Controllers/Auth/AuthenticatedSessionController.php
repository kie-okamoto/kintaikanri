<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;

class AuthenticatedSessionController extends Controller
{
  public function create()
  {
    return view('auth.login');
  }

  public function store(LoginRequest $request)
  {
    // LoginRequest 内で Auth::attempt 済み
    $request->session()->regenerate();
    return redirect()->intended('/attendance');
  }

  public function destroy(\Illuminate\Http\Request $request)
  {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
  }
}
