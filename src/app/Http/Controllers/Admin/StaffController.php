<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class StaffController extends Controller
{
    /**
     * スタッフ一覧画面を表示
     */
    public function index()
    {
        // 管理者以外（is_admin = false）のユーザー一覧を取得
        $users = User::where('is_admin', false)->get();

        return view('admin.attendance.staff_list', [
            'users' => $users,
        ]);
    }
}
