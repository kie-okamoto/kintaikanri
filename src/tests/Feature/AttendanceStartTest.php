<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceStartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤ボタンが正しく機能する
     */
    public function test_user_can_start_attendance()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(), // ✅ メール認証済みにする
        ]);
        Carbon::setTestNow(Carbon::parse('2025-07-26 09:00:00'));

        $this->actingAs($user)
            ->post('/attendance/start')
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_in' => '2025-07-26 09:00:00',
        ]);
    }

    /**
     * 出勤は一日一回のみできる
     */
    public function test_user_cannot_start_attendance_twice()
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::factory()->create([
            'email_verified_at' => now(), // ✅ メール認証済みにする
        ]);
        Carbon::setTestNow(Carbon::parse('2025-07-26 09:00:00'));

        // 1回目の出勤
        $this->actingAs($user)->post('/attendance/start');

        // 2回目の出勤（失敗想定）
        $response = $this->actingAs($user)
            ->from('/attendance')
            ->post('/attendance/start');

        $response->assertSessionHasErrors('clock_in');
    }


    /**
     * 出勤時刻が勤怠一覧で確認できる
     */
    public function test_attendance_clock_in_shows_in_list()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(), // ✅ メール認証済みにする
        ]);
        Carbon::setTestNow(Carbon::parse('2025-07-26 09:00:00'));

        $this->actingAs($user)->post('/attendance/start');

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('09:00');
    }
}
