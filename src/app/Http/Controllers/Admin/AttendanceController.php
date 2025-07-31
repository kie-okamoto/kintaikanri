<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use App\Models\AttendanceCorrectionRequest;
use App\Http\Requests\AttendanceUpdateRequest;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        $attendances = Attendance::with(['user', 'breaks'])
            ->where('date', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($attendances as $attendance) {
            $totalBreakSeconds = $attendance->breaks->sum(function ($break) {
                if ($break->start && $break->end) {
                    return Carbon::parse($break->end)->diffInSeconds(Carbon::parse($break->start));
                }
                return 0;
            });

            $attendance->calculated_break_duration = gmdate('H:i', $totalBreakSeconds);
        }

        return view('admin.attendance.index', compact('attendances', 'date'));
    }

    public function show($id, Request $request)
    {
        if ($id === 'new') {
            $date = $request->query('date');
            $userId = $request->query('user_id');

            if (!$date || !$userId) {
                abort(404);
            }

            $user = User::findOrFail($userId);

            $attendance = new Attendance();
            $attendance->date = $date;
            $attendance->clock_in = null;
            $attendance->clock_out = null;
            $attendance->note = null;
            $attendance->user_id = $user->id;
            $attendance->user = $user;
            $attendance->breaks = collect();

            $isApproved = false;

            $correction = AttendanceCorrectionRequest::whereHas('attendance', function ($q) use ($userId, $date) {
                $q->where('user_id', $userId)->where('date', $date);
            })->first();

            if ($correction && $correction->status === 'pending' && $correction->data) {
                $data = json_decode($correction->data, true);

                if (!empty($data['clock_in'])) {
                    $attendance->clock_in = Carbon::parse($data['clock_in']);
                }
                if (!empty($data['clock_out'])) {
                    $attendance->clock_out = Carbon::parse($data['clock_out']);
                }
                if (array_key_exists('note', $data)) {
                    $attendance->note = $data['note'];
                }
                if (!empty($data['breaks']) && is_array($data['breaks'])) {
                    $attendance->setRelation('breaks', collect($data['breaks'])->map(function ($break) {
                        return (object)[
                            'start' => !empty($break['start']) ? Carbon::parse($break['start']) : null,
                            'end'   => !empty($break['end']) ? Carbon::parse($break['end']) : null,
                        ];
                    }));
                }
            }

            return view('admin.attendance.show', compact('attendance', 'isApproved', 'correction', 'user'));
        }

        $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);
        $isApproved = $attendance->approval_status === 'approved';

        $correction = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)->first();

        if ($correction && $correction->status === 'pending' && $correction->data) {
            $data = json_decode($correction->data, true);

            if (!empty($data['clock_in'])) {
                $attendance->clock_in = Carbon::parse($data['clock_in']);
            }
            if (!empty($data['clock_out'])) {
                $attendance->clock_out = Carbon::parse($data['clock_out']);
            }
            if (array_key_exists('note', $data)) {
                $attendance->note = $data['note'];
            }
            if (!empty($data['breaks']) && is_array($data['breaks'])) {
                $attendance->setRelation('breaks', collect($data['breaks'])->map(function ($break) {
                    return (object)[
                        'start' => !empty($break['start']) ? Carbon::parse($break['start']) : null,
                        'end'   => !empty($break['end']) ? Carbon::parse($break['end']) : null,
                    ];
                }));
            }
        }

        return view('admin.attendance.show', [
            'attendance' => $attendance,
            'isApproved' => $isApproved,
            'correction' => $correction,
            'user' => $attendance->user,
        ]);
    }

    public function update(AttendanceUpdateRequest $request, $id)
    {
        if ($id === 'new') {
            return redirect()->back()->with('error', '新規勤怠に対する直接の更新はできません。');
        }

        $attendance = Attendance::with('breaks')->findOrFail($id);

        if ($attendance->approval_status === 'approved') {
            return redirect()->back()->with('error', '承認済みの勤怠は修正できません。');
        }

        $attendance->clock_in = $request->input('clock_in');
        $attendance->clock_out = $request->input('clock_out');
        $attendance->note = $request->input('note');
        $attendance->save();

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

        $attendance->load('breaks');

        $breakSeconds = $attendance->breaks->sum(function ($break) {
            if ($break->start && $break->end) {
                return Carbon::parse($break->end)->diffInSeconds(Carbon::parse($break->start));
            }
            return 0;
        });

        $workSeconds = 0;
        if ($attendance->clock_in && $attendance->clock_out) {
            $workSeconds = Carbon::parse($attendance->clock_out)->diffInSeconds(Carbon::parse($attendance->clock_in)) - $breakSeconds;
            if ($workSeconds < 0) {
                $workSeconds = 0;
            }
        }

        $attendance->break_duration = gmdate('H:i', $breakSeconds);
        $attendance->total_duration = gmdate('H:i', $workSeconds);
        $attendance->save();

        return redirect()->route('admin.attendance.show', $attendance->id)
            ->with('status', '勤怠情報を更新しました');
    }

    public function storeNew(AttendanceUpdateRequest $request)
    {
        $userId = $request->input('user_id');
        $date = $request->input('date');

        $existing = Attendance::where('user_id', $userId)
            ->where('date', $date)
            ->first();
        if ($existing) {
            return redirect()->back()->with('error', 'すでに勤怠データが存在します。');
        }

        $attendance = new Attendance();
        $attendance->user_id = $userId;
        $attendance->date = $date;
        $attendance->clock_in = $request->input('clock_in');
        $attendance->clock_out = $request->input('clock_out');
        $attendance->note = $request->input('note');
        $attendance->save();

        $breaks = $request->input('breaks', []);
        foreach ($breaks as $break) {
            if (!empty($break['start']) && !empty($break['end'])) {
                $attendance->breaks()->create([
                    'start' => $break['start'],
                    'end' => $break['end'],
                ]);
            }
        }

        $attendance->load('breaks');

        $breakSeconds = $attendance->breaks->sum(function ($break) {
            if ($break->start && $break->end) {
                return Carbon::parse($break->end)->diffInSeconds(Carbon::parse($break->start));
            }
            return 0;
        });

        $workSeconds = 0;
        if ($attendance->clock_in && $attendance->clock_out) {
            $workSeconds = Carbon::parse($attendance->clock_out)->diffInSeconds(Carbon::parse($attendance->clock_in)) - $breakSeconds;
            if ($workSeconds < 0) {
                $workSeconds = 0;
            }
        }

        $attendance->break_duration = gmdate('H:i', $breakSeconds);
        $attendance->total_duration = gmdate('H:i', $workSeconds);
        $attendance->save();

        return redirect()->route('admin.attendance.show', [
            'id' => $attendance->id,
            'tab' => 'admin',
        ])->with('status', '勤怠情報を新規登録しました');
    }

    public function staffList($id, Request $request)
    {
        $user = User::findOrFail($id);

        // 現在の月を取得（クエリパラメータに month があればそれを使用）
        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        // 勤怠データを該当月分取得
        $attendances = collect($user->attendances()
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get());

        $daysInMonth = $currentMonth->daysInMonth;
        $fullMonthAttendances = collect();

        // 該当月の日ごとにデータを準備
        for ($i = 0; $i < $daysInMonth; $i++) {
            $date = $currentMonth->copy()->addDays($i)->toDateString();

            $existing = $attendances->first(function ($a) use ($date) {
                return Carbon::parse($a->date)->toDateString() === $date;
            });

            $attendance = $existing ?: new Attendance([
                'date' => $date,
                'clock_in' => null,
                'clock_out' => null,
                'break_duration' => null,
                'total_duration' => null,
            ]);

            if (method_exists($attendance, 'calculateDurations')) {
                $attendance->calculateDurations();
            }

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

            $attendance = $existing ?: new Attendance([
                'date' => $date,
                'clock_in' => null,
                'clock_out' => null,
                'break_duration' => null,
                'total_duration' => null,
            ]);

            if (method_exists($attendance, 'calculateDurations')) {
                $attendance->calculateDurations();
            }

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

    public function exportCsv($id, $month = null)
    {
        $user = User::findOrFail($id);

        $attendances = $user->attendances()
            ->with('breaks')
            ->when($month, function ($query, $month) {
                return $query->where('date', 'like', $month . '%');
            })
            ->orderBy('date')
            ->get();

        $csvHeader = ['日付', '出勤時間', '退勤時間', '休憩時間合計', '勤務時間合計'];
        $filename = $user->name . '_attendance_' . ($month ?? now()->format('Y-m')) . '.csv';

        $callback = function () use ($attendances, $csvHeader) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($stream, $csvHeader);

            foreach ($attendances as $attendance) {
                $clockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;
                $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;

                $breakSeconds = $attendance->breaks->sum(function ($break) {
                    if ($break->start && $break->end) {
                        return Carbon::parse($break->end)->diffInSeconds(Carbon::parse($break->start));
                    }
                    return 0;
                });

                $workSeconds = 0;
                if ($clockIn && $clockOut) {
                    $workSeconds = $clockOut->diffInSeconds($clockIn) - $breakSeconds;
                    if ($workSeconds < 0) {
                        $workSeconds = 0;
                    }
                }

                $breakDuration = gmdate('H:i', $breakSeconds);
                $workDuration = gmdate('H:i', $workSeconds);

                fputcsv($stream, [
                    Carbon::parse($attendance->date)->format('Y-m-d'),
                    $clockIn ? $clockIn->format('H:i') : '',
                    $clockOut ? $clockOut->format('H:i') : '',
                    $breakDuration,
                    $workDuration,
                ]);
            }

            fclose($stream);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
