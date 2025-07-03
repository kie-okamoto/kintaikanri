<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages()
    {
        return [
            // 未入力時のメッセージ
            'name.required' => 'お名前を入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email'   => '有効なメールアドレス形式で入力してください',
            'password.required' => 'パスワードを入力してください',

            // パスワードの条件違反
            'password.min' => 'パスワードは8文字以上で入力してください',

            // 確認用パスワードと一致しない
            'password.confirmed' => 'パスワードと一致しません',
        ];
    }
}
