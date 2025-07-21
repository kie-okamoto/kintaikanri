<?php

return [

    'defaults' => [
        'guard' => 'web',           // デフォルトは一般ユーザー
        'passwords' => 'users',
    ],

    // ▼ 認証ガード（users用とadmins用を分離）
    'guards' => [
        'web' => [                  // 一般ユーザー用
            'driver' => 'session',
            'provider' => 'users',
        ],
        'admin' => [                // 管理者用
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    // ▼ ユーザープロバイダ（データ取得元）
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
    ],

    // ▼ パスワードリセット設定（users用のみ定義）
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
        // 管理者のパスワードリセットが必要なら以下を有効にする
        // 'admins' => [
        //     'provider' => 'admins',
        //     'table' => 'password_resets',
        //     'expire' => 60,
        //     'throttle' => 60,
        // ],
    ],

    // ▼ パスワード確認の有効時間（デフォルト 3時間）
    'password_timeout' => 10800,

];
