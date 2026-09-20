<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_reset_link_can_be_requested_for_existing_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => '  Student@Example.COM ',
        ]);

        $response->assertSessionHas(
            'status',
            'إذا كان هذا البريد مسجلًا لدينا، ستصلك رسالة فيها رابط الاستعادة.'
        );

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_receives_same_generic_response(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'unknown@example.com',
        ]);

        $response->assertSessionHas(
            'status',
            'إذا كان هذا البريد مسجلًا لدينا، ستصلك رسالة فيها رابط الاستعادة.'
        );

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $token = null;

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('login'));

        $user->refresh();

        $this->assertTrue(
            Hash::check('new-password123', $user->password)
        );
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $token = null;

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'first-password123',
            'password_confirmation' => 'first-password123',
        ])->assertRedirect(route('login'));

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'second-password123',
            'password_confirmation' => 'second-password123',
        ]);

        $response->assertSessionHasErrors('email');

        $user->refresh();

        $this->assertTrue(
            Hash::check('first-password123', $user->password)
        );
    }

    public function test_expired_reset_token_is_rejected(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $token = null;

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update([
                'created_at' => now()->subMinutes(61),
            ]);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_reset_link_requests_are_rate_limited_per_source(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this
                ->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
                ->post(route('password.email'), [
                    'email' => "unknown{$attempt}@example.com",
                ])
                ->assertStatus(302);
        }

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->post(route('password.email'), [
                'email' => 'another@example.com',
            ]);

        $response->assertStatus(429);
    }
}
