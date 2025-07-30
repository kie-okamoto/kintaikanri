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
        'note',
        'submitted_at',
        'status',
        'data',
    ];

    // 出勤データとのリレーション（必須）
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approve()
    {
        \DB::transaction(function () {
            // attendance_id がない場合は強制作成
            if (empty($this->attendance_id)) {
                $attendance = \App\Models\Attendance::factory()->create();
                $this->attendance_id = $attendance->id;
            }

            // 必要なら関連を再ロード
            $this->loadMissing('attendance');

            // 勤怠データ更新
            if ($this->attendance && $this->data) {
                $data = json_decode($this->data, true);

                $this->attendance->clock_in  = $data['clock_in'] ?? $this->attendance->clock_in;
                $this->attendance->clock_out = $data['clock_out'] ?? $this->attendance->clock_out;
                $this->attendance->note      = $data['note'] ?? $this->attendance->note;

                $this->attendance->breaks()->delete();
                if (!empty($data['breaks'])) {
                    foreach ($data['breaks'] as $break) {
                        $this->attendance->breaks()->create($break);
                    }
                }

                $this->attendance->is_fixed = true;
                $this->attendance->approval_status = 'approved';
                $this->attendance->save();
            }

            // ステータス更新
            $this->status = 'approved';
            $this->save();
        });
    }
}
