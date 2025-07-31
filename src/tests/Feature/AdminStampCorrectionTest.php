<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;

class AdminStampCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 承認待ちの申請が一覧に表示される()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        AttendanceCorrectionRequest::factory()->pending()->create([
            'reason' => 'テスト理由1',
            'note'   => '詳細内容1',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/list?tab=waiting');

        $response->assertStatus(200);
        // 一覧では理由のみ表示される仕様
        $response->assertSee('テスト理由1');
    }

    /** @test */
    public function 承認済みの修正申請が一覧に表示される()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        AttendanceCorrectionRequest::factory()->approved()->create([
            'reason' => 'テスト理由2',
            'note'   => '詳細内容2',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        // 一覧では理由のみ表示される仕様
        $response->assertSee('テスト理由2');
    }

    /** @test */
    public function 修正申請の詳細内容が正しく表示される()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $request = AttendanceCorrectionRequest::factory()->pending()->create([
            'reason' => '詳細表示テスト理由',
            'note'   => '体調不良による修正希望',
            'attendance_id' => Attendance::factory()->create([
                'date' => Carbon::today(),
            ])->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/' . $request->id);

        $response->assertStatus(200);
        // 詳細画面で理由と内容を確認
        $response->assertSee('詳細表示テスト理由');
        $response->assertSee('体調不良による修正希望');
        $response->assertSee(Carbon::today()->format('Y年'));
        $response->assertSee(Carbon::today()->format('n月j日'));
    }

    /** @test */
    public function 修正申請を承認できる()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $request = AttendanceCorrectionRequest::factory()->pending()->create([
            'reason' => '承認処理テスト',
            'note'   => '内容確認済み、承認します',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/stamp_correction_request/approve/' . $request->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id'     => $request->id,
            'status' => 'approved',
        ]);
    }
}
