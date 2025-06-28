<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 5) as $i) {
            User::create([
                'name' => "一般ユーザー{$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('test123'),
                'email_verified_at' => now(),
            ]);
        }
    }
}
