<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        return [
            'user_id' => 1, // テストごとに上書き可能
            'date' => Carbon::today(),
            'clock_in' => null,
            'clock_out' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
