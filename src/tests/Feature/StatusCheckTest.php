<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;


class StatusCheckTest extends TestCase
{
    use RefreshDatabase;

    protected $workUser;
    protected $breakUser;
    protected $outUser;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 7, 23, 12, 0, 0, 'Asia/Tokyo'));
        $now = Carbon::now();
        $today = $now->toDateString();

        // 出勤中ユーザーと出勤レコード（clock_inあり・clock_outなし）
        $this->workUser = User::factory()->create(['email_verified_at' => now()]);
        Attendance::create([
            'user_id' => $this->workUser->id,
            'date' => $today,
            'clock_in' => $now->copy()->subHours(3),
            'clock_out' => null,
        ]);

        // 休憩中ユーザーと出勤＋休憩レコード（clock_outなし、break.endなし）
        $this->breakUser = User::factory()->create(['email_verified_at' => now()]);
        $attendance = Attendance::create([
            'user_id' => $this->breakUser->id,
            'date' => $today,
            'clock_in' => $now->copy()->subHours(3),
            'clock_out' => null,
        ]);
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'start' => $now->copy()->subMinutes(30),
            'end' => null,
        ]);

        // 退勤済ユーザーと出勤レコード（clock_in・clock_out両方あり）
        $this->outUser = User::factory()->create(['email_verified_at' => now()]);
        Attendance::create([
            'user_id' => $this->outUser->id,
            'date' => $today,
            'clock_in' => $now->copy()->subHours(8),
            'clock_out' => $now->copy()->subHours(1),
        ]);
    }

    public function test_勤務外の場合は勤務外と表示される()
    {
        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        // 勤務外：レコードを作成しない
        $offUser = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($offUser)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合は出勤中と表示される()
    {
        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        $response = $this->actingAs($this->workUser)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合は休憩中と表示される()
    {
        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        $response = $this->actingAs($this->breakUser)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合は退勤済と表示される()
    {
        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        $response = $this->actingAs($this->outUser)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
