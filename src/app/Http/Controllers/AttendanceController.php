<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\AttendanceCorrectionRequest;

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
            ->with('breaks')
            ->first();

        if ($attendance) {
            $attendance->clock_out = Carbon::now();
            $attendance->save();
        }

        Session::put('attendance_status', 'done');
        return redirect()->route('attendance.register');
    }

    public function list(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', auth()->id())
            ->whereBetween('date', [
                $startOfMonth->copy()->startOfDay(),
                $endOfMonth->copy()->endOfDay()
            ])
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $daysInMonth = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dayString = $date->format('Y-m-d');
            if ($attendances->has($dayString)) {
                $attendance = $attendances->get($dayString);

                $totalBreakSeconds = $attendance->breaks->sum(function ($break) {
                    return ($break->start && $break->end)
                        ? Carbon::parse($break->end)->diffInSeconds(Carbon::parse($break->start))
                        : 0;
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
        $attendance = Attendance::with(['user', 'breaks', 'correctionRequest'])->findOrFail($id);

        // 修正申請が「承認待ち」の場合だけロック
        $isPending = optional($attendance->correctionRequest)->status === 'pending';

        return view('attendance.show', compact('attendance', 'isPending'));
    }

    public function updateRequest(Request $request, $id)
    {
        \Log::info('修正申請処理開始', [
            'attendance_id' => $id,
            'reason' => $request->input('note'),
        ]);

        $attendance = Attendance::with('breaks')->findOrFail($id);

        // すでに「pending」の申請があれば再申請不可
        if ($attendance->correctionRequest && $attendance->correctionRequest->status === 'pending') {
            return redirect()->route('attendance.show', $id)
                ->with('error', 'すでに修正申請が提出されています。承認をお待ちください。');
        }

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'reason' => $request->input('note'),
            'submitted_at' => now(),
            'status' => 'pending',
        ]);

        \Log::info('修正申請作成完了');

        return redirect()->route('attendance.show', $id)
            ->with('success', '修正申請を送信しました。');
    }
}
