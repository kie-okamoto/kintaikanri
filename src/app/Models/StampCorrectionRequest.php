<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_date',
        'reason',
        'requested_at',
        'status',
        'note',
    ];

    // リレーション（ユーザー）
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
