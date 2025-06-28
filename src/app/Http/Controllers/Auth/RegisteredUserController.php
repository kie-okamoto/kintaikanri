<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class RegisteredUserController extends Controller
{
    /**
     * 登録フォーム表示
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * 登録処理
     */
    public function store(Request $request)
    {
        // バリデーション
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // ユーザー作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ✅ メール認証トリガー
        event(new Registered($user));

        // ✅ 自動ログイン
        Auth::login($user);

        // 認証ページへリダイレクト
        return redirect()->route('verification.notice');
    }
}
