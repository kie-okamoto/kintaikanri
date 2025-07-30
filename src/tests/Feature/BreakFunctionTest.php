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

        $this->post(route('attendance.breakStart'))->assertRedirect();

        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'end'           => null,
        ]);
    }

    /** @test */
    public function 休憩戻ボタンが正しく機能する()
    {
        $this->actingAs($this->user);

        $this->post(route('attendance.breakStart'));
        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('date', Carbon::today())
            ->first();
        $break = $attendance->breaks()->whereNull('end')->first();

        $this->post(route('attendance.breakEnd'))->assertRedirect();

        $this->assertNotNull($break->fresh()->end);
    }

    /** @test */
    public function 休憩は一日に複数回取れる()
    {
        $this->actingAs($this->user);

        for ($i = 0; $i < 2; $i++) {
            $this->post(route('attendance.breakStart'));
            $this->post(route('attendance.breakEnd'));
        }

        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        $this->assertEquals(2, $attendance->breaks()->count());
    }

    /** @test */
    public function 休憩戻は一日に複数回できる()
    {
        $this->actingAs($this->user);

        foreach (range(1, 3) as $i) {
            $this->post(route('attendance.breakStart'));
            $this->post(route('attendance.breakEnd'));
        }

        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        $this->assertEquals(3, $attendance->breaks()->whereNotNull('end')->count());
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
