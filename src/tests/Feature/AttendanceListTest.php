<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function 自分が行った勤怠情報が全て表示されている()
    {
        $this->actingAs($this->user);

        Attendance::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subDays(2),
        ]);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        foreach (Attendance::all() as $attendance) {
            $response->assertSee(Carbon::parse($attendance->date)->format('Y-m-d'));
        }
    }

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $this->actingAs($this->user);

        $currentMonth = Carbon::now()->format('Y-m');
        $this->get('/attendance/list')
            ->assertSee($currentMonth);
    }

    /** @test */
    public function 前月ボタン押下で前月の情報が表示される()
    {
        $this->actingAs($this->user);

        $prevMonth = Carbon::now()->subMonth()->format('Y-m');
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse($prevMonth . '-10'),
        ]);

        $this->get('/attendance/list?month=' . $prevMonth)
            ->assertSee($prevMonth);
    }

    /** @test */
    public function 翌月ボタン押下で翌月の情報が表示される()
    {
        $this->actingAs($this->user);

        $nextMonth = Carbon::now()->addMonth();
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $nextMonth->copy()->day(5),
        ]);

        $formattedMonth = $nextMonth->format('Y年m月');

        $this->get('/attendance/list?month=' . $nextMonth->format('Y-m'))
            ->assertSee($formattedMonth);
    }


    /** @test */
    public function 詳細ボタン押下でその日の勤怠詳細画面に遷移する()
    {
        $this->actingAs($this->user);

        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
        ]);

        $this->get('/attendance/' . $attendance->id)
            ->assertStatus(200)
            ->assertSee(Carbon::today()->format('Y-m-d'));
    }
}
