<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧表示（管理者）
     */
    public function index()
    {
        $attendances = Attendance::with('user')->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.attendance.index', compact('attendances'));
    }

    /**
     * 勤怠詳細表示（管理者）
     */
    public function show($id)
    {
        $attendance = Attendance::with('user')->findOrFail($id);
        return view('admin.attendance.show', compact('attendance'));
    }

    /**
     * スタッフ別勤怠一覧（管理者）
     */
    public function staffList($id)
    {
        $user = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.attendance.staff_list', compact('user', 'attendances'));
    }
}
