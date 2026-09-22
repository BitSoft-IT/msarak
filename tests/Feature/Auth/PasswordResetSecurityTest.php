<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Q-02 (Phase 1) — Password-reset security coverage.
 *
 * Targets token storage, the 60-minute expiry boundary, token reuse,
 * mismatched token/email pairs and remember-token rotation. The functional
 * happy path is already covered by PasswordResetTest (H-02) and is not
 * repeated here. Production code is never modified by these tests.
 */
class PasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_reset_token_is_hashed_in_the_database(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
        ]);

        // Act
        $this->post(route('password.email'), [
            'email' => 'student@example.com',
        ]);

        $plainToken = $this->captureResetToken($user);

        // Assert — the stored value must never be the plaintext token.
        $storedToken = DB::table('password_reset_tokens')
            ->where('email', 'student@example.com')
            ->value('token');

        $this->assertNotSame($plainToken, $storedToken);
        $this->assertTrue(Hash::check($plainToken, (string) $storedToken));
    }

    #[DataProvider('expiryBoundaryProvider')]
    public function test_reset_link_respects_the_60_minute_boundary(int $seconds, bool $shouldStillWork): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.email'), [
            'email' => 'student@example.com',
        ]);

        $plainToken = $this->captureResetToken($user);

        // Act — advance time to the edge of the expiry window.
        $this->travelTo(now()->addSeconds($seconds));

        $response = $this->post(route('password.store'), [
            'token' => $plainToken,
            'email' => 'student@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        // Assert
        if ($shouldStillWork) {
            $response->assertRedirect(route('login'));
            $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
        } else {
            $response->assertSessionHasErrors('email');
            $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        }
    }

    /**
     * @return array<string, array{0: int, 1: bool}>
     */
    public static function expiryBoundaryProvider(): array
    {
        return [
            // 3598 seconds (2 seconds before expiry) — reduced from 3599 to
            // avoid a millisecond race condition in CI between capturing the
            // token and travelling forward in time.
            'two seconds before expiry' => [3598, true],
            'one second after expiry' => [3601, false],
            'one minute before expiry' => [59 * 60, true],
            'one minute after expiry' => [61 * 60, false],
        ];
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.email'), [
            'email' => 'student@example.com',
        ]);

        $this->captureResetToken($user);

        // Act — a fabricated token that never existed.
        $response = $this->post(route('password.store'), [
            'token' => 'bogus-token-that-does-not-exist',
            'email' => 'student@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        // Assert — the old password must survive a bad token.
        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_reset_token_cannot_be_used_with_a_mismatched_email(): void
    {
        // Arrange — a token issued for one account.
        Notification::fake();

        $victim = User::factory()->create([
            'email' => 'victim@example.com',
            'password' => Hash::make('victim-password'),
        ]);

        $other = User::factory()->create([
            'email' => 'other@example.com',
            'password' => Hash::make('other-password'),
        ]);

        $this->post(route('password.email'), [
            'email' => 'victim@example.com',
        ]);

        $victimToken = $this->captureResetToken($victim);

        // Act — the same token submitted against a different account.
        $response = $this->post(route('password.store'), [
            'token' => $victimToken,
            'email' => 'other@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        // Assert — neither account's password changes.
        $response->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('victim-password', $victim->fresh()->password));
        $this->assertTrue(Hash::check('other-password', $other->fresh()->password));
    }

    public function test_remember_token_is_rotated_after_a_password_reset(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
        ]);

        $oldRememberToken = $user->remember_token;

        $this->post(route('password.email'), [
            'email' => 'student@example.com',
        ]);

        $plainToken = $this->captureResetToken($user);

        // Act
        $this->post(route('password.store'), [
            'token' => $plainToken,
            'email' => 'student@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        // Assert — "remember me" artefacts from the old password are voided.
        $this->assertNotSame($oldRememberToken, $user->fresh()->remember_token);
    }

    public function test_expired_reset_token_still_cannot_be_reused(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => 'student@example.com',
        ]);

        $plainToken = $this->captureResetToken($user);

        $this->post(route('password.store'), [
            'token' => $plainToken,
            'email' => 'student@example.com',
            'password' => 'first-password123',
            'password_confirmation' => 'first-password123',
        ])->assertRedirect(route('login'));

        // Act — attempt to reuse the consumed token well inside the window.
        $response = $this->post(route('password.store'), [
            'token' => $plainToken,
            'email' => 'student@example.com',
            'password' => 'second-password123',
            'password_confirmation' => 'second-password123',
        ]);

        // Assert — the first password survives the replay attempt.
        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('first-password123', $user->fresh()->password));
    }

    /**
     * Extract the plaintext token from the notification sent to the user.
     */
    private function captureResetToken(User $user): string
    {
        $token = null;

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        return (string) $token;
    }
}
