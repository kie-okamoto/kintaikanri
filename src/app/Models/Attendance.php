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
        'break_duration',
        'total_duration',
        'note',
        'approval_status',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'date' => 'date',
    ];

    // ユーザーとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 休憩時間とのリレーション（1対多）
    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    /**
     * 勤務合計時間と休憩時間を自動計算（複数休憩対応）
     */
    public function calculateDurations()
    {
        if ($this->clock_in && $this->clock_out) {
            $workSeconds = $this->clock_out->diffInSeconds($this->clock_in);

            // 休憩時間（複数）を合計
            $breakSeconds = $this->breaks->reduce(function ($carry, $break) {
                if ($break->start && $break->end) {
                    return $carry + Carbon::parse($break->end)->diffInSeconds(Carbon::parse($break->start));
                }
                return $carry;
            }, 0);

            $this->break_duration = gmdate('H:i:s', $breakSeconds);
            $this->total_duration = gmdate('H:i:s', max($workSeconds - $breakSeconds, 0));
        }
    }

    /**
     * 保存前に自動で合計時間を計算
     * - breaksが未ロードでもロードして計算できるように修正
     */
    protected static function booted()
    {
        static::saving(function ($attendance) {
            // breaks がロード済みでなければロードする
            if (!$attendance->relationLoaded('breaks')) {
                $attendance->load('breaks');
            }

            $attendance->calculateDurations();
        });
    }
}
