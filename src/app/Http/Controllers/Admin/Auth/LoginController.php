<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminLoginRequest;

class LoginController extends Controller
{
    /**
     * ログイン画面の表示
     */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /**
     * ログイン処理
     */
    public function login(AdminLoginRequest $request)
    {
        // AdminLoginRequest内でログイン試行済みなので、ここでは成功処理のみ
        $request->session()->regenerate();
        return redirect()->route('admin.attendance.list');
    }

    /**
     * ログアウト処理
     */
    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin.login');
    }
}
