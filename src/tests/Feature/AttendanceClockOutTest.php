<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function 退勤ボタンが正しく機能する()
    {
        $this->actingAs($this->user);

        // Carbonを固定
        Carbon::setTestNow(Carbon::create(2025, 7, 30, 9, 0, 0));
        $today = Carbon::today()->toDateString();

        // 出勤
        $this->post(route('attendance.start'))->assertRedirect();

        // 出勤確認（dateを文字列化して比較）
        $attendanceBefore = Attendance::latest()->first();
        $this->assertEquals($today, Carbon::parse($attendanceBefore->date)->toDateString());

        // 退勤
        $this->post(route('attendance.clockOut'))->assertRedirect();

        // 再取得
        $attendanceAfter = Attendance::where('user_id', $this->user->id)
            ->whereDate('date', $today)
            ->first();

        $this->assertNotNull($attendanceAfter, '退勤後の勤怠レコードが取得できません');
        $this->assertNotNull($attendanceAfter->clock_out, '退勤時刻が設定されていません');

        $this->get(route('attendance.index'))
            ->assertSee('退勤済');
    }




    /** @test */
    public function 退勤時刻が勤怠一覧画面で確認できる()
    {
        $this->actingAs($this->user);

        // 出勤と退勤（ルート名を修正）
        $this->post(route('attendance.start'));
        $this->post(route('attendance.clockOut'));

        $attendance = Attendance::latest()->first();
        $clockOutTime = Carbon::parse($attendance->clock_out)->format('H:i');

        // 勤怠一覧画面に退勤時刻が正しく表示されるか確認
        $this->get(route('attendance.list'))
            ->assertStatus(200)
            ->assertSee($clockOutTime);
    }
}
