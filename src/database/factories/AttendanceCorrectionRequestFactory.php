<?php

namespace Database\Factories;

use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AttendanceCorrectionRequestFactory extends Factory
{
    protected $model = AttendanceCorrectionRequest::class;

    public function definition(): array
    {
        $clockIn  = Carbon::now()->format('H:i');
        $clockOut = Carbon::now()->addHours(8)->format('H:i');

        return [
            'reason'        => '詳細表示テスト理由',
            'note'          => '体調不良による修正希望',
            'submitted_at'  => now(),
            'status'        => 'pending',
            'data'          => json_encode([
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
                'note'      => '体調不良による修正希望',
                'breaks'    => [],
            ]),
        ];
    }

    public function configure()
    {
        return $this->for(
            Attendance::factory()->state([
                'date' => Carbon::today()->toDateString(),
            ])
        );
    }

    public function approved(): Factory
    {
        return $this->state(fn() => ['status' => 'approved']);
    }

    public function pending(): Factory
    {
        return $this->state(fn() => ['status' => 'pending']);
    }
}
