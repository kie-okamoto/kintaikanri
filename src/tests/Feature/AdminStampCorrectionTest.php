<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

class AdminStampCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が承認待ちの修正申請を一覧で確認できる
     */
    public function test_admin_can_view_pending_requests()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $pendingRequest = StampCorrectionRequest::factory()->create([
            'status' => '承認待ち',
            'reason' => 'テスト理由1',
            'note' => '詳細内容1',
        ]);

        $response = $this->actingAs($admin)->get('/admin/stamp_correction_request/list?tab=admin');

        $response->assertStatus(200);
        $response->assertSee('テスト理由1');
        $response->assertSee('詳細内容1');
    }

    /**
     * 管理者が承認済みの修正申請を一覧で確認できる
     */
    public function test_admin_can_view_approved_requests()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $approvedRequest = StampCorrectionRequest::factory()->create([
            'status' => '承認済み',
            'reason' => 'テスト理由2',
            'note' => '詳細内容2',
        ]);

        $response = $this->actingAs($admin)->get('/admin/stamp_correction_request/list?tab=admin');

        $response->assertStatus(200);
        $response->assertSee('テスト理由2');
        $response->assertSee('詳細内容2');
    }

    /**
     * 管理者が修正申請の詳細内容を確認できる
     */
    public function test_admin_can_view_request_details()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $request = StampCorrectionRequest::factory()->create([
            'status' => '承認待ち',
            'reason' => '詳細表示テスト理由',
            'note' => '体調不良による修正希望',
            'target_date' => Carbon::today(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/stamp_correction_request/approve/' . $request->id . '?tab=admin');

        $response->assertStatus(200);
        $response->assertSee('詳細表示テスト理由');
        $response->assertSee('体調不良による修正希望');
        $response->assertSee(Carbon::today()->format('Y-m-d'));
    }

    /**
     * 管理者が修正申請を承認できる
     */
    public function test_admin_can_approve_request()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $request = StampCorrectionRequest::factory()->create([
            'status' => '承認待ち',
            'reason' => '承認処理テスト',
            'note' => '内容確認済み、承認します',
        ]);

        $response = $this->actingAs($admin)->post('/admin/stamp_correction_request/approve/' . $request->id . '?tab=admin', [
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $request->id,
            'status' => '承認済み',
        ]);
    }
}
