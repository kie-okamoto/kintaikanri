<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        // ユーザー作成
        $this->user = User::factory()->create([
            'name' => 'テストユーザー',
            'email_verified_at' => now(), // 認証済み
        ]);

        // 出勤データ作成
        Carbon::setTestNow(Carbon::parse('2025-07-30 09:00:00'));
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::parse('2025-07-30 09:00:00'),
            'clock_out' => Carbon::parse('2025-07-30 18:00:00'),
        ]);

        // 休憩データ作成
        AttendanceBreak::create([
            'attendance_id' => $this->attendance->id,
            'start' => Carbon::parse('2025-07-30 12:00:00'),
            'end' => Carbon::parse('2025-07-30 12:30:00'),
        ]);
    }

    /** @test */
    public function 勤怠詳細画面の名前がログインユーザーの氏名になっている()
    {
        $this->actingAs($this->user)
            ->get("/attendance/{$this->attendance->id}")
            ->assertStatus(200)
            ->assertSee($this->user->name);
    }

    /** @test */
    public function 勤怠詳細画面の日付が選択した日付になっている()
    {
        $this->actingAs($this->user)
            ->get("/attendance/{$this->attendance->id}")
            ->assertStatus(200)
            ->assertSee('2025年')
            ->assertSee('7月30日');
    }


    /** @test */
    public function 出勤退勤時間がログインユーザーの打刻と一致している()
    {
        $this->actingAs($this->user)
            ->get("/attendance/{$this->attendance->id}")
            ->assertStatus(200)
            ->assertSee($this->attendance->clock_in->format('H:i'))
            ->assertSee($this->attendance->clock_out->format('H:i'));
    }

    /** @test */
    public function 休憩時間がログインユーザーの打刻と一致している()
    {
        $break = $this->attendance->breaks()->first();

        $this->actingAs($this->user)
            ->get("/attendance/{$this->attendance->id}")
            ->assertStatus(200)
            ->assertSee(Carbon::parse($break->start)->format('H:i'))
            ->assertSee(Carbon::parse($break->end)->format('H:i'));
    }
}
