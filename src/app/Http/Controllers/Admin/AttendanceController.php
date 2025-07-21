<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use App\Models\StampCorrectionRequest;
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



    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);

        // 勤怠が「承認済み」かどうか判定（ボタン表示制御用）
        $isApproved = $attendance->approval_status === 'approved';

        // 該当ユーザーの修正申請データを取得
        $correction = StampCorrectionRequest::where('user_id', $attendance->user_id)
            ->where('target_date', $attendance->date)
            ->first();

        // 修正申請中かつデータが存在する場合、仮反映する
        if ($correction && $correction->status === 'pending' && $correction->data) {
            $data = json_decode($correction->data, true);

            // 出退勤
            if (!empty($data['clock_in'])) {
                $attendance->clock_in = \Carbon\Carbon::parse($data['clock_in']);
            }
            if (!empty($data['clock_out'])) {
                $attendance->clock_out = \Carbon\Carbon::parse($data['clock_out']);
            }

            // 備考
            if (array_key_exists('note', $data)) {
                $attendance->note = $data['note'];
            }

            // 休憩（配列→コレクションにしてbreaksに仮反映）
            if (!empty($data['breaks']) && is_array($data['breaks'])) {
                $attendance->setRelation('breaks', collect($data['breaks'])->map(function ($break) {
                    return (object)[
                        'start' => !empty($break['start']) ? \Carbon\Carbon::parse($break['start']) : null,
                        'end'   => !empty($break['end']) ? \Carbon\Carbon::parse($break['end']) : null,
                    ];
                }));
            }
        }

        return view('admin.attendance.show', compact('attendance', 'isApproved', 'correction'));
    }



    public function update(AttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::with(['breaks', 'correctionRequest'])->findOrFail($id);

        // 承認済は編集不可
        if ($attendance->approval_status === 'approved') {
            return redirect()
                ->route('admin.attendance.show', $attendance->id)
                ->with('error', 'この勤怠は既に承認済みのため、再修正はできません。');
        }

        // 入力値の反映
        $attendance->clock_in = $request->input('clock_in');
        $attendance->clock_out = $request->input('clock_out');
        $attendance->note = $request->input('note');
        $attendance->is_fixed = true;

        // 休憩再登録
        $attendance->breaks()->delete();
        if ($request->has('breaks')) {
            foreach ($request->input('breaks') as $break) {
                if (!empty($break['start']) || !empty($break['end'])) {
                    $attendance->breaks()->create([
                        'start' => $break['start'] ?? null,
                        'end' => $break['end'] ?? null,
                    ]);
                }
            }
        }

        $attendance->save();

        // 修正申請が存在し、承認待ちなら申請内容も更新
        if ($attendance->approval_status === 'pending') {
            $correctionRequest = StampCorrectionRequest::where('user_id', $attendance->user_id)
                ->where('target_date', $attendance->date)
                ->first();

            if ($correctionRequest) {
                $correctionRequest->update([
                    'clock_in' => $attendance->clock_in,
                    'clock_out' => $attendance->clock_out,
                    'note' => $attendance->note,
                ]);
            }
        }

        return redirect()
            ->route('admin.attendance.show', $attendance->id)
            ->with('status', 'updated');
    }

    public function staffList($id)
    {
        $user = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

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

            // 🔶 文字化け防止：UTF-8 BOM を付与
            fwrite($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($stream, $csvHeader);

            foreach ($attendances as $attendance) {
                $clockIn = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in) : null;
                $clockOut = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out) : null;

                // 休憩時間（秒）
                $breakSeconds = $attendance->breaks->sum(function ($break) {
                    if ($break->start && $break->end) {
                        return \Carbon\Carbon::parse($break->end)->diffInSeconds(\Carbon\Carbon::parse($break->start));
                    }
                    return 0;
                });

                // 勤務時間（秒）＝ 退勤 - 出勤 - 休憩
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
                    \Carbon\Carbon::parse($attendance->date)->format('Y-m-d'),
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
