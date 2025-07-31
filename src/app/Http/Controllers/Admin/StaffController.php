<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    /**
     * スタッフ一覧画面を表示
     */
    public function index()
    {
        $users = User::where('is_admin', false)->get();

        return view('admin.attendance.staff_list', [
            'users' => $users,
        ]);
    }
}
