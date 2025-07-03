<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        // user_id = 1〜5 のユーザーに対してダミーデータを生成
        foreach (range(1, 5) as $userId) {
            // 6月後半（6/17〜6/30 のうち平日10日間）
            $this->generateDummyData($userId, '2025-06-17', 10);

            // 7月前半（7/1〜7/14 のうち平日10日間）
            $this->generateDummyData($userId, '2025-07-01', 10);
        }
    }

    private function generateDummyData($userId, $startDate, $days)
    {
        $date = Carbon::parse($startDate);
        $count = 0;

        while ($count < $days) {
            // 平日のみ（※土日除外）
            if (!in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $clockIn = $date->copy()->setTime(9, rand(0, 15)); // 9:00〜9:15
                $breakStart = $date->copy()->setTime(12, 0);        // 12:00〜13:00
                $breakEnd = $date->copy()->setTime(13, 0);
                $clockOut = $date->copy()->setTime(18, rand(0, 15)); // 18:00〜18:15

                // 休憩時間・総勤務時間計算
                $breakDuration = gmdate('H:i', $breakEnd->diffInSeconds($breakStart));
                $workSeconds = $clockOut->diffInSeconds($clockIn) - 3600; // −休憩1時間
                $totalDuration = gmdate('H:i', max(0, $workSeconds));

                Attendance::create([
                    'user_id' => $userId,
                    'date' => $date->toDateString(),
                    'clock_in' => $clockIn,
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                    'clock_out' => $clockOut,
                    'break_duration' => $breakDuration,
                    'total_duration' => $totalDuration,
                    'approval_status' => 'approved',
                ]);

                $count++;
            }

            $date->addDay();
        }
    }
}
