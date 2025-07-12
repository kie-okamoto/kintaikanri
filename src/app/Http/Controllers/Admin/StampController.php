<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrectionRequest;

class StampController extends Controller
{
    // 一覧表示（承認・未承認をタブで切り替え）
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'waiting');

        $query = AttendanceCorrectionRequest::with('attendance.user')->orderBy('submitted_at', 'desc');

        if ($tab === 'approved') {
            $query->where('status', 'approved');
        } else {
            $query->where('status', '!=', 'approved');
        }

        $requests = $query->get();

        return view('admin.stamp.index', compact('requests', 'tab'));
    }

    // 詳細表示
    public function show($id)
    {
        $request = AttendanceCorrectionRequest::with('attendance.user')->findOrFail($id);

        return view('admin.stamp.show', compact('request'));
    }

    // 承認処理（モデルの approve() を呼び出すだけ）
    public function approve($id)
    {
        $request = AttendanceCorrectionRequest::findOrFail($id);

        $request->approve(); // モデルに定義した承認処理を使用

        return response()->json(['message' => '承認しました']);
    }
}
