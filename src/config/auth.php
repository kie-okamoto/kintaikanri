<?php

return [

    'defaults' => [
        'guard' => 'web',           // デフォルトは一般ユーザー用
        'passwords' => 'users',
    ],

    // ▼ 認証ガードの設定（user, admin を分離）
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'admin' => [ // ★追加：管理者用ガード
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    // ▼ ユーザープロバイダの設定
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'admins' => [ // ★追加：管理者モデル
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
    ],

    // ▼ パスワードリセット設定（必要なら admin 用も個別設定可能）
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
        // 任意：admin 用パスワードリセット（未使用なら不要）
        // 'admins' => [
        //     'provider' => 'admins',
        //     'table' => 'password_resets',
        //     'expire' => 60,
        //     'throttle' => 60,
        // ],
    ],

    // ▼ パスワード確認の有効時間（秒）
    'password_timeout' => 10800,

];
