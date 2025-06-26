<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // 一般ユーザー
        User::create([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 管理者ユーザー（adminsテーブルを使っている場合）
        \DB::table('admins')->insert([
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpass'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
