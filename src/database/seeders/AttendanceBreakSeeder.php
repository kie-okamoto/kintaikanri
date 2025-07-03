<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AttendanceBreakSeeder extends Seeder
{
    public function run()
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            AttendanceBreak::create([
                'attendance_id' => $attendance->id,
                'start' => $attendance->break_start,
                'end' => $attendance->break_end,
            ]);
        }
    }
}
