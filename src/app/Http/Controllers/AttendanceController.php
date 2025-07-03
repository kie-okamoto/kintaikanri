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

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['clock_in' => Carbon::now()]
        );

        $attendance->breaks()->create([
            'start' => Carbon::now(),
            'end' => null,
        ]);

        Session::put('attendance_status', 'break');
        return redirect()->route('attendance.register');
    }

    public function breakOut(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->with('breaks')
            ->first();

        if ($attendance) {
            $lastBreak = $attendance->breaks()->whereNull('end')->latest()->first();
            if ($lastBreak) {
                $lastBreak->end = Carbon::now();
                $lastBreak->save();
            }
            $attendance->load('breaks');
            $attendance->save(); // 自動再計算される
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
            ->with('breaks')
            ->first();

        if ($attendance) {
            $attendance->clock_out = Carbon::now();
            $attendance->save(); // 自動再計算される
        }

        Session::put('attendance_status', 'done');
        return redirect()->route('attendance.register');
    }

    public function list(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // 勤怠 + 休憩も取得
        $attendances = Attendance::with('breaks')
            ->where('user_id', auth()->id())
            ->whereBetween('date', [
                $startOfMonth->copy()->startOfDay(),
                $endOfMonth->copy()->endOfDay()
            ])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        $daysInMonth = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dayString = $date->format('Y-m-d');
            if ($attendances->has($dayString)) {
                $attendance = $attendances->get($dayString);

                // 休憩時間の計算
                $totalBreakSeconds = $attendance->breaks->sum(function ($break) {
                    if ($break->start && $break->end) {
                        return Carbon::parse($break->end)->diffInSeconds(Carbon::parse($break->start));
                    }
                    return 0;
                });

                $attendance->calculated_break_duration = gmdate('H:i', $totalBreakSeconds);

                $daysInMonth[] = $attendance;
            } else {
                $daysInMonth[] = (object)[
                    'date' => $dayString,
                    'clock_in' => null,
                    'clock_out' => null,
                    'calculated_break_duration' => null,
                    'total_duration' => null,
                    'id' => null,
                ];
            }
        }

        return view('attendance.list', [
            'attendances' => $daysInMonth,
            'month' => $month,
        ]);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);
        $isPending = $attendance->approval_status === 'pending';

        return view('attendance.show', compact('attendance', 'isPending'));
    }

    public function updateRequest(Request $request, $id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);

        $attendance->clock_in = $request->input('clock_in');
        $attendance->clock_out = $request->input('clock_out');
        $attendance->note = $request->input('note');
        $attendance->approval_status = 'pending';
        $attendance->save();

        // 休憩の再登録
        $attendance->breaks()->delete();
        $breaks = $request->input('breaks', []);
        foreach ($breaks as $break) {
            if (!empty($break['start']) && !empty($break['end'])) {
                $attendance->breaks()->create([
                    'start' => $break['start'],
                    'end' => $break['end'],
                ]);
            }
        }

        return redirect()->route('stamp.list')->with('success', '修正申請を保存しました。');
    }
}
