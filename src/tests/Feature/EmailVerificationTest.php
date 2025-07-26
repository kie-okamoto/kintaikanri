<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ユーザー登録時に認証メールが送信される()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->assertNotNull($user->email);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    /** @test */
    public function 認証リンクにアクセスすると認証済みになり勤怠登録画面にリダイレクトされる()
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $response->assertRedirect('/attendance');
    }

    /** @test */
    public function 未認証ユーザーはメール認証画面にリダイレクトされる()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertRedirect('/email/verify');
    }
}