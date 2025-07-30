<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;

class AttendanceDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date'    => Carbon::today()->toDateString(),
            'clock_in' => Carbon::parse('09:00'),
            'clock_out' => Carbon::parse('18:00'),
        ]);
    }

    /** @test */
    public function 出勤時間が退勤時間より後の場合エラーメッセージが表示される()
    {
        $this->actingAs($this->user)
            ->post("/attendance/{$this->attendance->id}/update-request", [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'note' => 'テスト'
            ])
            ->assertSessionHasErrors(['clock_in']);
    }

    /** @test */
    public function 休憩開始が退勤時間より後の場合エラーメッセージが表示される()
    {
        $this->actingAs($this->user)
            ->post("/attendance/{$this->attendance->id}/update-request", [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [['start' => '19:00', 'end' => '19:30']],
                'note' => 'テスト'
            ])
            ->assertSessionHasErrors(['breaks.0.start']);
    }

    /** @test */
    public function 休憩終了が退勤時間より後の場合エラーメッセージが表示される()
    {
        $this->actingAs($this->user)
            ->post("/attendance/{$this->attendance->id}/update-request", [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [['start' => '17:00', 'end' => '19:00']],
                'note' => 'テスト'
            ])
            ->assertSessionHasErrors(['breaks.0.end']);
    }

    /** @test */
    public function 備考欄が未入力の場合エラーメッセージが表示される()
    {
        $this->actingAs($this->user)
            ->post("/attendance/{$this->attendance->id}/update-request", [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'note' => ''
            ])
            ->assertSessionHasErrors(['note']);
    }

    /** @test */
    public function 修正申請が実行され承認待ち一覧に表示される()
    {
        $this->actingAs($this->user)
            ->post("/attendance/{$this->attendance->id}/update-request", [
                'clock_in' => '09:30',
                'clock_out' => '18:00',
                'note' => '修正申請'
            ])
            ->assertRedirect("/attendance/{$this->attendance->id}");

        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $this->attendance->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function 承認待ちにログインユーザーの申請が表示される()
    {
        AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'pending'
        ]);

        $this->actingAs($this->user)
            ->get('/stamp_correction_request/list')
            ->assertSee('承認待ち');
    }

    /** @test */
    public function 承認済みに管理者が承認した申請が表示される()
    {
        AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'approved'
        ]);

        $this->actingAs($this->user)
            ->get('/stamp_correction_request/list')
            ->assertSee('承認済み');
    }

    /** @test */
    public function 詳細ボタンを押すと申請詳細画面に遷移する()
    {
        $request = AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'pending'
        ]);

        $this->actingAs($this->user)
            ->get("/stamp_correction_request/{$request->id}")
            ->assertStatus(200)
            ->assertSee('申請詳細');
    }
}
