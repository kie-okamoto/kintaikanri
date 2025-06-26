<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        return redirect()->route('attendance.register');
    }

    public function register()
    {
        Carbon::setLocale('ja');

        $today = Carbon::now()->isoFormat('YYYY年M月D日(ddd)');
        $time = Carbon::now()->format('H:i');

        $status = Session::get('attendance_status', 'off');

        return view('attendance.register', [
            'status' => $status,
            'date' => $today,
            'time' => $time,
        ]);
    }

    public function start(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['clock_in' => Carbon::now()]
        );

        Session::put('attendance_status', 'working');
        return redirect()->route('attendance.register');
    }

    public function breakIn(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            $attendance->break_start = Carbon::now();
            $attendance->save();
        }

        Session::put('attendance_status', 'break');
        return redirect()->route('attendance.register');
    }

    public function breakOut(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            $attendance->break_end = Carbon::now();
            $attendance->save();
        }

        Session::put('attendance_status', 'working');
        return redirect()->route('attendance.register');
    }

    public function end(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            $attendance->clock_out = Carbon::now();

            // 休憩時間を計算
            if ($attendance->break_start && $attendance->break_end) {
                $breakSeconds = Carbon::parse($attendance->break_end)
                    ->diffInSeconds(Carbon::parse($attendance->break_start));
                $attendance->break_duration = gmdate('H:i', $breakSeconds);
            } else {
                $attendance->break_duration = null;
            }

            // 勤務合計時間（休憩除く）
            if ($attendance->clock_in && $attendance->clock_out) {
                $workSeconds = Carbon::parse($attendance->clock_out)
                    ->diffInSeconds(Carbon::parse($attendance->clock_in));

                if ($attendance->break_duration) {
                    $breakParts = explode(':', $attendance->break_duration);
                    $breakSeconds = ((int)$breakParts[0] * 60 + (int)$breakParts[1]) * 60;
                    $workSeconds -= $breakSeconds;
                }

                $attendance->total_duration = gmdate('H:i', max(0, $workSeconds));
            }

            $attendance->save();
        }

        Session::put('attendance_status', 'done');
        return redirect()->route('attendance.register');
    }

    public function list(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $attendances = Attendance::whereYear('date', substr($month, 0, 4))
            ->whereMonth('date', substr($month, 5, 2))
            ->where('user_id', auth()->id())
            ->orderBy('date')
            ->get();

        $attendances->each(function ($attendance) {
            $attendance->calculateDurations();
        });

        return view('attendance.list', [
            'attendances' => $attendances,
            'month' => $month,
        ]);
    }

    public function show($id)
    {
        $attendance = Attendance::with('user')->findOrFail($id);

        // 修正申請済かどうかを確認（status列を仮定：'pending', 'approved' など）
        $isPending = $attendance->approval_status === 'pending';

        return view('attendance.show', compact('attendance', 'isPending'));
    }

    public function updateRequest(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // 入力値を取得
        $attendance->clock_in = $request->input('clock_in');
        $attendance->clock_out = $request->input('clock_out');

        // 1件目の休憩（あれば）
        if ($request->has('breaks.0.start') && $request->has('breaks.0.end')) {
            $attendance->break_start = $request->input('breaks.0.start');
            $attendance->break_end = $request->input('breaks.0.end');
        }

        // 備考
        $attendance->note = $request->input('note');

        // 申請状態（未承認）
        $attendance->approval_status = 'pending';

        // 保存
        $attendance->save();

        return redirect()->route('stamp.list')->with('success', '修正申請を保存しました。');
    }
}
