<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザー作成
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function 当日勤怠一覧が表示される()
    {
        $today = Carbon::today();

        Attendance::factory()->create([
            'date' => $today->toDateString(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/attendance/list?tab=admin');

        $response->assertStatus(200)
            ->assertSee($today->isoFormat('YYYY年MM月DD日'))
            ->assertSee($today->format('Y/m/d'));
    }

    /** @test */
    public function 前日勤怠一覧が表示される()
    {
        $yesterday = Carbon::yesterday();

        Attendance::factory()->create([
            'date' => $yesterday->toDateString(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/attendance/list?tab=admin&date=' . $yesterday->toDateString());

        $response->assertStatus(200)
            ->assertSee($yesterday->isoFormat('YYYY年MM月DD日'))
            ->assertSee($yesterday->format('Y/m/d'));
    }

    /** @test */
    public function 翌日勤怠一覧が表示される()
    {
        $tomorrow = Carbon::tomorrow();

        Attendance::factory()->create([
            'date' => $tomorrow->toDateString(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/attendance/list?tab=admin&date=' . $tomorrow->toDateString());

        $response->assertStatus(200)
            ->assertSee($tomorrow->isoFormat('YYYY年MM月DD日'))
            ->assertSee($tomorrow->format('Y/m/d'));
    }
}
