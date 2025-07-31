<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminUserInfoTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者作成
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // 一般ユーザー作成
        $this->user = User::factory()->create([
            'name' => '一般ユーザーA',
            'email' => 'userA@example.com',
            'email_verified_at' => now(),
        ]);

        // 勤怠データ（当月・前月・翌月）
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subMonth()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->addMonth()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    /** @test */
    public function スタッフ一覧に全ユーザーの氏名とメールが表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/staff/list?tab=admin');

        $response->assertStatus(200)
            ->assertSee($this->user->name)
            ->assertSee($this->user->email);
    }

    /** @test */
    public function 勤怠一覧に選択ユーザーの勤怠情報が表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/attendance/staff/' . $this->user->id . '?tab=admin');

        $response->assertStatus(200)
            ->assertSee($this->user->name)
            ->assertSee(Carbon::today()->isoFormat('MM/DD（dd）'));
    }


    /** @test */
    public function 勤怠一覧で前月ボタン押下時に前月情報が表示される()
    {
        $prevMonth = Carbon::today()->subMonth()->format('Y-m');

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/attendance/staff/' . $this->user->id . '?tab=admin&month=' . $prevMonth);

        $response->assertStatus(200)
            ->assertSee($prevMonth);
    }

    /** @test */
    public function 勤怠一覧で翌月ボタン押下時に翌月情報が表示される()
    {
        $nextMonth = Carbon::today()->addMonth()->format('Y-m');

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/attendance/staff/' . $this->user->id . '?tab=admin&month=' . $nextMonth);

        $response->assertStatus(200)
            ->assertSee($nextMonth);
    }

    /** @test */
    public function 勤怠一覧の詳細ボタンで勤怠詳細画面に遷移する()
    {
        $attendance = Attendance::where('user_id', $this->user->id)->first();

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/attendance/' . $attendance->id . '?tab=admin');

        $response->assertStatus(200)
            ->assertSee($this->user->name)
            ->assertSee(Carbon::parse($attendance->date)->format('Y-m-d'));
    }
}
