<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $attendance;

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

        // 勤怠データ作成
        $this->attendance = Attendance::factory()->create([
            'date' => Carbon::today()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'note' => 'テスト備考'
        ]);
    }

    /** @test */
    public function 勤怠詳細画面に選択したデータが表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/attendance/' . $this->attendance->id . '?tab=admin');

        $response->assertStatus(200)
            ->assertSee($this->attendance->user->name)
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('テスト備考');
    }

    /** @test */
    public function 出勤時間が退勤時間より後の場合エラーが表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put('/admin/attendance/' . $this->attendance->id, [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'note' => '修正'
            ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後の場合エラーが表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put('/admin/attendance/' . $this->attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    ['start' => '19:00', 'end' => '19:30']
                ],
                'note' => '修正'
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩開始時間が勤務時間外です'
        ]);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後の場合エラーが表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put('/admin/attendance/' . $this->attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    ['start' => '17:00', 'end' => '19:30']
                ],
                'note' => '修正'
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩開始時間が勤務時間外です'
        ]);
    }

    /** @test */
    public function 備考欄が未入力の場合エラーが表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put('/admin/attendance/' . $this->attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'note' => ''
            ]);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください'
        ]);
    }
}
