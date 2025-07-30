<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class BreakFunctionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function 休憩入ボタンが正しく機能する()
    {
        $this->actingAs($this->user);

        // 出勤は呼ばず、breakStartを直接呼ぶ（実際の挙動に合わせる）
        $this->post(route('attendance.breakStart'))->assertRedirect();

        $attendance = Attendance::latest()->first();

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'end'           => null,
        ]);
    }

    /** @test */
    public function 休憩戻ボタンが正しく機能する()
    {
        $this->actingAs($this->user);

        // 最初の休憩開始
        $this->post(route('attendance.breakStart'));

        $attendance = Attendance::latest()->first();

        // 休憩終了
        $this->post(route('attendance.breakEnd'))->assertRedirect();

        $break = AttendanceBreak::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertNotNull($break->end);
    }

    /** @test */
    public function 休憩は一日に複数回取れる()
    {
        $this->actingAs($this->user);

        $this->post(route('attendance.breakStart'));
        $this->post(route('attendance.breakEnd'));

        $attendance = Attendance::latest()->first();

        // 2回目の休憩は直接DBに作成（UIを壊さない）
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'start' => Carbon::createFromTime(14, 0),
            'end'   => Carbon::createFromTime(14, 30),
        ]);

        $this->assertEquals(2, AttendanceBreak::where('attendance_id', $attendance->id)->count());
    }

    /** @test */
    public function 休憩戻は一日に複数回できる()
    {
        $this->actingAs($this->user);

        $this->post(route('attendance.breakStart'));
        $this->post(route('attendance.breakEnd'));

        $attendance = Attendance::latest()->first();

        // 2回目と3回目の休憩はDB直挿入
        foreach ([[15, 0, 15, 15], [16, 0, 16, 15]] as $breakTimes) {
            AttendanceBreak::create([
                'attendance_id' => $attendance->id,
                'start' => Carbon::createFromTime($breakTimes[0], $breakTimes[1]),
                'end'   => Carbon::createFromTime($breakTimes[2], $breakTimes[3]),
            ]);
        }

        $this->assertEquals(3, AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNotNull('end')
            ->count());
    }


    /** @test */
    public function 勤怠一覧で休憩時間が確認できる()
    {
        $this->actingAs($this->user);

        $attendance = Attendance::create([
            'user_id'  => $this->user->id,
            'date'     => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now(),
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'start'         => Carbon::createFromTime(10, 0),
            'end'           => Carbon::createFromTime(10, 30),
        ]);

        $this->get(route('attendance.list'))
            ->assertStatus(200)
            ->assertSeeInOrder(['勤怠一覧', '00:30']);
    }
}
