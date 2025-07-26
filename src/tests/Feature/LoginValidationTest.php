<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function メールアドレスが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/login', [
            'email' => '', // 未入力
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function パスワードが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => '', // 未入力
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function 登録内容と一致しない場合_エラーメッセージが表示される()
    {
        // 正常なユーザー登録
        User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 間違ったログイン情報
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email'); // 認証失敗時は email にエラーが返る
    }
}
