<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'break_duration',
        'total_duration', // DB保存するので含める
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 勤務合計時間と休憩時間を自動計算
     */
    public function calculateDurations()
    {
        if ($this->clock_in && $this->clock_out) {
            $workSeconds = $this->clock_out->diffInSeconds($this->clock_in);

            $breakSeconds = 0;
            if ($this->break_start && $this->break_end) {
                $breakSeconds = $this->break_end->diffInSeconds($this->break_start);
                $this->break_duration = gmdate('H:i:s', $breakSeconds);
            }

            $totalSeconds = max($workSeconds - $breakSeconds, 0);
            $this->total_duration = gmdate('H:i:s', $totalSeconds);
        }
    }


    /**
     * 保存時に自動で合計時間をセット
     */
    protected static function booted()
    {
        static::saving(function ($attendance) {
            $attendance->calculateDurations();
        });
    }
}
