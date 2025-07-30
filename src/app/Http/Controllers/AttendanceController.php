<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use App\Http\Requests\AttendanceUpdateRequest;

class AttendanceController extends Controller
{
    public function index()
    {
        Carbon::setLocale('ja');
        $now = Carbon::now();
        $today = $now->isoFormat('YYYY年M月D日(ddd)');
        $time = $now->format('H:i');
        $todayDate = $now->toDateString();

        $user = Auth::user();

        \Log::debug('ログインユーザーID: ' . $user->id);

        // 勤怠データを取得（breaksも含む）
        $attendance = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereDate('date', $todayDate)
            ->first();

        \Log::debug('今日の日付: ' . $todayDate);
        \Log::debug('ユーザーID: ' . $user->id);

        if (!$attendance) {
            \Log::debug('該当の勤怠データが見つかりませんでした。');
        } else {
            \Log::debug('勤怠データ取得: ', $attendance->toArray());
        }


        // 勤務ステータス判定
        if (!$attendance) {
            $status = '勤務外';
        } elseif ($attendance->clock_out !== null) {
            $status = '退勤済';
        } elseif ($attendance->breaks && $attendance->breaks->where('end', null)->isNotEmpty()) {
            $status = '休憩中';
        } else {
            $status = '出勤中';
        }

        return view('attendance.register', [
            'status' => $status,
            'date' => $today,
            'time' => $time,
        ]);
    }


    public function start(Request $request)
    {
        $now = Carbon::now();
        $user = Auth::user();
        $today = $now->toDateString();

        $alreadyClockedIn = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->exists();

        if ($alreadyClockedIn) {
            return back()->withErrors([
                'clock_in' => 'すでに出勤済みです。'
            ]);
        }


        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => $now,
            'approval_status' => 'pending',
        ]);

        return redirect()->route('attendance.index');
    }

    public function breakIn(Request $request)
    {
        $now = Carbon::now();
        $user = Auth::user();
        $today = $now->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['clock_in' => $now]
        );

        $attendance->breaks()->create([
            'start' => $now,
            'end' => null,
        ]);

        Session::put('attendance_status', 'break');
        return redirect()->route('attendance.index');
    }

    public function breakOut(Request $request)
    {
        $now = Carbon::now();
        $user = Auth::user();
        $today = $now->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->with('breaks')
            ->first();

        if ($attendance) {
            // 休憩終了処理
            $lastBreak = $attendance->breaks()
                ->whereNull('end')
                ->orderBy('start', 'desc')
                ->first();

            if ($lastBreak) {
                $lastBreak->update(['end' => $now]);
            }
        }

        Session::put('attendance_status', 'working');
        return redirect()->route('attendance.index');
    }


    public function end(Request $request)
    {
        $now = Carbon::now();
        $user = Auth::user();
        $today = $now->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->with('breaks')
            ->first();

        if ($attendance) {
            $attendance->clock_out = $now;
            $attendance->save();
        }

        Session::put('attendance_status', 'done');
        return redirect()->route('attendance.index');
    }

    public function list(Request $request)
    {
        $now = Carbon::now();
        $month = $request->input('month', $now->format('Y-m'));

        $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$startOfMonth->copy()->startOfDay(), $endOfMonth->copy()->endOfDay()])
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

    public function show(Request $request, $id)
    {
        $now = Carbon::now();
        $user = auth()->user();
        $today = $now->toDateString();
        $targetDate = $request->input('date') ?? $today;

        if ($id === 'new') {
            $attendance = new Attendance([
                'user_id' => $user->id,
                'date' => $targetDate,
                'clock_in' => null,
                'clock_out' => null,
                'note' => null,
            ]);
            $attendance->setRelation('breaks', collect());
            $isPending = false;
            $breakCount = 0;
        } else {
            $attendance = Attendance::with(['user', 'breaks', 'correctionRequest'])
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            if (Carbon::parse($attendance->date)->gt($now)) {
                abort(403, '未来日の勤怠は表示できません');
            }

            $isPending = optional($attendance->correctionRequest)->status === 'pending';

            if ($isPending && $attendance->correctionRequest->data) {
                $data = json_decode($attendance->correctionRequest->data, true);

                $attendance->clock_in = !empty($data['clock_in']) ? Carbon::parse($data['clock_in']) : $attendance->clock_in;
                $attendance->clock_out = !empty($data['clock_out']) ? Carbon::parse($data['clock_out']) : $attendance->clock_out;
                $attendance->note = array_key_exists('note', $data) ? $data['note'] : $attendance->note;

                if (!empty($data['breaks']) && is_array($data['breaks'])) {
                    $attendance->setRelation('breaks', collect($data['breaks'])->map(function ($break) {
                        return (object)[
                            'start' => !empty($break['start']) ? Carbon::parse($break['start']) : null,
                            'end'   => !empty($break['end']) ? Carbon::parse($break['end']) : null,
                        ];
                    }));
                }
            }

            $breakCount = $attendance->breaks->count();
            $targetDate = $attendance->date;
        }

        return view('attendance.show', compact('attendance', 'isPending', 'breakCount', 'targetDate'));
    }

    public function updateRequest(AttendanceUpdateRequest $request, $id)
    {
        $now = Carbon::now();
        $user = auth()->user();

        if ($id === 'new') {
            $targetDate = $request->input('date') ?? $now->toDateString(); // ← hiddenで受け取る
            $attendance = Attendance::firstOrCreate(
                ['user_id' => $user->id, 'date' => $targetDate],
                ['approval_status' => 'pending']
            );
        } else {
            $attendance = Attendance::with('breaks')->findOrFail($id);
        }

        if ($attendance->correctionRequest && $attendance->correctionRequest->status === 'pending') {
            return redirect()->route('attendance.show', $attendance->id)
                ->with('error', 'すでに修正申請が提出されています。承認をお待ちください。');
        }

        $rawBreaks = $request->input('breaks', []);
        $breaks = [];
        foreach ($rawBreaks as $break) {
            $breaks[] = [
                'start' => $break['start'] ?? null,
                'end'   => $break['end'] ?? null,
            ];
        }

        $correctionData = [
            'clock_in'  => $request->input('clock_in'),
            'clock_out' => $request->input('clock_out'),
            'note'      => $request->input('note'),
            'breaks'    => $breaks,
        ];

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'reason'        => $request->input('note'),
            'submitted_at'  => $now,
            'status'        => 'pending',
            'data'          => json_encode($correctionData),
        ]);

        return redirect()->route('attendance.show', $attendance->id)
            ->with('success', '修正申請を送信しました。承認をお待ちください。');
    }
}
