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
        'data',
    ];

    // 出勤データとのリレーション（必須）
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // 承認処理
    public function approve()
    {
        if ($this->attendance && $this->data) {
            $data = json_decode($this->data, true);

            $this->attendance->clock_in = $data['clock_in'];
            $this->attendance->clock_out = $data['clock_out'];
            $this->attendance->note = $data['note'];

            // 休憩の再登録
            $this->attendance->breaks()->delete();
            foreach ($data['breaks'] as $break) {
                if (!empty($break['start']) && !empty($break['end'])) {
                    $this->attendance->breaks()->create([
                        'start' => $break['start'],
                        'end'   => $break['end'],
                    ]);
                }
            }

            // ✅ 修正フラグと承認ステータスを更新
            $this->attendance->is_fixed = true;
            $this->attendance->approval_status = 'approved';
            $this->attendance->save();
        }

        $this->status = 'approved';
        $this->save();
    }
}
