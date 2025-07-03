<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\RegisterRequest;

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
    public function store(RegisterRequest $request) // ★ Request → RegisterRequest に変更
    {
        // バリデーションは RegisterRequest で実行済み

        // ユーザー作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // メール認証イベント
        event(new Registered($user));

        // 自動ログイン
        Auth::login($user);

        // 認証ページへリダイレクト
        return redirect()->route('verification.notice');
    }
}
