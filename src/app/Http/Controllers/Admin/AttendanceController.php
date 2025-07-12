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

        // ユーザーと休憩を読み込む
        $attendances = Attendance::with(['user', 'breaks'])
            ->where('date', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        // 休憩合計時間を計算して一時プロパティに追加
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
        // user情報とbreaks、correctionRequestも必要ならwithで追加
        $attendance = Attendance::with(['user', 'breaks', 'correctionRequest'])->findOrFail($id);

        // 承認ステータスを渡す（Bladeでボタン制御に使う）
        $isApproved = $attendance->approval_status === 'approved';

        return view('admin.attendance.show', compact('attendance', 'isApproved'));
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

    public function exportCsv($id, $month = null)
    {
        // ユーザー取得
        $user = User::findOrFail($id);

        // 月指定がない場合は今月を使用
        $month = $month ?? now()->format('Y-m');

        $parsedMonth = \Carbon\Carbon::parse($month);

        // 勤怠データ取得（対象月）
        $attendances = $user->attendances()
            ->whereYear('date', $parsedMonth->year)
            ->whereMonth('date', $parsedMonth->month)
            ->with('breaks')
            ->orderBy('date')
            ->get();

        // CSVヘッダー
        $csvHeader = ['日付', '出勤時間', '退勤時間', '休憩時間合計', '勤務時間合計'];
        $filename = $user->name . '_attendance_' . $parsedMonth->format('Y-m') . '.csv';

        // ストリームでCSV出力
        $callback = function () use ($attendances, $csvHeader) {
            $stream = fopen('php://output', 'w');

            // Excel用：BOM付きで文字化け防止
            fwrite($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($stream, $csvHeader);

            foreach ($attendances as $attendance) {
                $clockIn = optional($attendance->clock_in)->format('H:i');
                $clockOut = optional($attendance->clock_out)->format('H:i');

                $break = $attendance->break_duration ? substr($attendance->break_duration, 0, 5) : '';
                $total = $attendance->total_duration ? substr($attendance->total_duration, 0, 5) : '';

                fputcsv($stream, [
                    \Carbon\Carbon::parse($attendance->date)->format('Y-m-d'),
                    $clockIn,
                    $clockOut,
                    $break,
                    $total,
                ]);
            }

            fclose($stream);
        };

        // CSV出力レスポンス
        return response()->stream($callback, 200, [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}",
        ]);
    }
}
