<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
            'password' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '有効なメールアドレス形式で入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->hasErrors() && !Auth::guard('admin')->attempt($this->only('email', 'password'))) {
                $validator->errors()->add('email', 'ログイン情報が登録されていません');
            }
        });
    }

    private function hasErrors(): bool
    {
        return $this->getValidatorInstance()->errors()->isNotEmpty();
    }
}
