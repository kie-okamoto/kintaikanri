<?php

namespace Database\Factories;

use App\Models\AttendanceBreak;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AttendanceBreakFactory extends Factory
{
    protected $model = AttendanceBreak::class;

    public function definition()
    {
        return [
            'attendance_id' => Attendance::factory(), // または固定値 1 にしたい場合は 1
            'start' => Carbon::now()->subMinutes(30),
            'end' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
