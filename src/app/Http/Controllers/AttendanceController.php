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

        $isPending = optional($attendance->correctionRequest)->status === 'pending';

        // 修正申請中かつデータが存在する場合、仮表示データで上書き
        if ($isPending && $attendance->correctionRequest->data) {
            $data = json_decode($attendance->correctionRequest->data, true);

            // 出勤・退勤・備考
            $attendance->clock_in = !empty($data['clock_in']) ? Carbon::parse($data['clock_in']) : $attendance->clock_in;
            $attendance->clock_out = !empty($data['clock_out']) ? Carbon::parse($data['clock_out']) : $attendance->clock_out;
            $attendance->note = array_key_exists('note', $data) ? $data['note'] : $attendance->note;

            // 休憩：配列 → Collection<(object)>
            if (!empty($data['breaks']) && is_array($data['breaks'])) {
                $attendance->setRelation('breaks', collect($data['breaks'])->map(function ($break) {
                    return (object)[
                        'start' => !empty($break['start']) ? Carbon::parse($break['start']) : null,
                        'end'   => !empty($break['end']) ? Carbon::parse($break['end']) : null,
                    ];
                }));
            }
        }

        return view('attendance.show', compact('attendance', 'isPending'));
    }



    public function updateRequest(Request $request, $id)
    {
        \Log::info('修正申請処理開始', [
            'attendance_id' => $id,
            'clock_in' => $request->input('clock_in'),
            'clock_out' => $request->input('clock_out'),
            'note' => $request->input('note'),
            'breaks' => $request->input('breaks'),
        ]);

        $attendance = Attendance::with('breaks')->findOrFail($id);

        // すでに申請中なら拒否
        if ($attendance->correctionRequest && $attendance->correctionRequest->status === 'pending') {
            return redirect()->route('attendance.show', $id)
                ->with('error', 'すでに修正申請が提出されています。承認をお待ちください。');
        }

        // breaks の整形（name="breaks[0][start]"形式を正しく配列化）
        $rawBreaks = $request->input('breaks', []);
        $breaks = [];
        foreach ($rawBreaks as $index => $break) {
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
            'submitted_at'  => now(),
            'status'        => 'pending',
            'data'          => json_encode($correctionData),
        ]);

        \Log::info('修正申請内容を保存', ['correction_data' => $correctionData]);

        return redirect()->route('attendance.show', $id)
            ->with('success', '修正申請を送信しました。承認をお待ちください。');
    }
}
