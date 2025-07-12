<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'reason',
        'submitted_at',
        'status',
    ];

    // 出勤データとのリレーション（必須）
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // 承認処理
    public function approve()
    {
        if ($this->attendance) {
            $this->attendance->note = $this->reason;
            $this->attendance->approval_status = 'approved';
            $this->attendance->save();
        }

        $this->status = 'approved';
        $this->save();
    }
}
