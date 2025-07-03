<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        $attendances = Attendance::with('user')
            ->where('date', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.attendance.index', compact('attendances', 'date'));
    }

    public function show($id)
    {
        $attendance = Attendance::with('user')->findOrFail($id);
        return view('admin.attendance.show', compact('attendance'));
    }

    public function staffList($id)
    {
        $user = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)->orderBy('created_at', 'desc')->paginate(10);

        $date = Carbon::now()->isoFormat('YYYY年MM月DD日（ddd）', 'ja');

        return view('admin.attendance.staff_list', compact('user', 'attendances', 'date'));
    }

    public function staffDetail($id, Request $request)
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $attendances = collect($user->attendances()
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get());

        $daysInMonth = $currentMonth->daysInMonth;
        $fullMonthAttendances = collect();

        for ($i = 0; $i < $daysInMonth; $i++) {
            $date = $currentMonth->copy()->addDays($i)->toDateString();

            $existing = $attendances->first(function ($a) use ($date) {
                return Carbon::parse($a->date)->toDateString() === $date;
            });

            if ($existing) {
                $attendance = $existing;
            } else {
                $attendance = new Attendance([
                    'date' => $date,
                    'clock_in' => null,
                    'clock_out' => null,
                    'break_duration' => null,
                    'total_duration' => null,
                ]);
            }

            if (method_exists($attendance, 'calculateDurations')) {
                $attendance->calculateDurations();
            }

            // ▼ ここで時刻を整形して渡す（nullでもOK）
            $attendance->formatted_clock_in = $attendance->clock_in
                ? Carbon::parse($attendance->clock_in)->format('H:i')
                : '';

            $attendance->formatted_clock_out = $attendance->clock_out
                ? Carbon::parse($attendance->clock_out)->format('H:i')
                : '';

            $fullMonthAttendances->push($attendance);
        }

        return view('admin.attendance.staff_detail', [
            'user' => $user,
            'attendances' => $fullMonthAttendances,
            'currentMonth' => $currentMonth,
            'previousMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function exportCsv($id)
    {
        $user = User::findOrFail($id);
        $attendances = $user->attendances()->with('breaks')->orderBy('date')->get();

        $csvHeader = ['日付', '出勤時間', '退勤時間', '休憩時間合計', '勤務時間合計'];
        $filename = $user->name . '_attendance.csv';

        $callback = function () use ($attendances, $csvHeader) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);

            foreach ($attendances as $attendance) {
                $clockIn = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in) : null;
                $clockOut = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out) : null;

                // 休憩時間の合計（秒単位）
                $breakSeconds = $attendance->breaks->reduce(function ($carry, $break) {
                    if ($break->start && $break->end) {
                        return $carry + \Carbon\Carbon::parse($break->end)->diffInSeconds(\Carbon\Carbon::parse($break->start));
                    }
                    return $carry;
                }, 0);

                // 勤務時間 = 出退勤差分 - 休憩
                $totalSeconds = 0;
                if ($clockIn && $clockOut) {
                    $totalSeconds = $clockOut->diffInSeconds($clockIn) - $breakSeconds;
                    $totalSeconds = max($totalSeconds, 0);
                }

                fputcsv($file, [
                    $attendance->date,
                    $clockIn ? $clockIn->format('H:i') : '',
                    $clockOut ? $clockOut->format('H:i') : '',
                    gmdate('H:i', $breakSeconds),
                    gmdate('H:i', $totalSeconds),
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
