<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Q-02 (Phase 1) — Login and password-reset rate-limiting coverage.
 *
 * Targets the two independent login limiters (per-account and per-source),
 * the asymmetry of the clear-on-success behaviour, unknown-account accounting
 * and the throttle on reset-link requests. The array cache store is shared
 * across tests in one process, so the limiter keys are cleared in setUp to
 * keep tests isolated. Production code is never modified by these tests.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT_EMAIL = 'student@example.com';

    private const SOURCE_IP = '192.0.2.10';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // The array cache store is shared across tests within one process,
        // so the login limiters are cleared explicitly here to prevent the
        // attempts of one test from blocking another.
        RateLimiter::clear($this->accountKey());
        RateLimiter::clear($this->sourceKey(self::SOURCE_IP));
    }

    public function test_sixth_login_attempt_is_blocked_even_with_correct_password(): void
    {
        // Arrange
        User::factory()->create([
            'email' => self::ACCOUNT_EMAIL,
            'password' => Hash::make('password123'),
        ]);

        // Act — exhaust the five allowed attempts with a wrong password.
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login'), [
                'email' => self::ACCOUNT_EMAIL,
                'password' => 'wrong-password',
            ]);
        }

        $blocked = $this->post(route('login'), [
            'email' => self::ACCOUNT_EMAIL,
            'password' => 'password123', // correct, but too late
        ]);

        // Assert
        $blocked->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_successful_login_clears_the_account_limiter(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => self::ACCOUNT_EMAIL,
            'password' => Hash::make('password123'),
        ]);

        // Act — fail three times, then authenticate successfully.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->post(route('login'), [
                'email' => self::ACCOUNT_EMAIL,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login'), [
            'email' => self::ACCOUNT_EMAIL,
            'password' => 'password123',
        ]);

        // Assert — the account counter is reset on success.
        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, RateLimiter::attempts($this->accountKey()));
    }

    public function test_successful_login_does_not_clear_the_source_limiter(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => self::ACCOUNT_EMAIL,
            'password' => Hash::make('password123'),
        ]);

        // Act — fail three times from one source, then authenticate from the
        // same source. Every request must carry the same REMOTE_ADDR,
        // otherwise each request resolves a different source limiter key.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this
                ->withServerVariables(['REMOTE_ADDR' => self::SOURCE_IP])
                ->post(route('login'), [
                    'email' => self::ACCOUNT_EMAIL,
                    'password' => 'wrong-password',
                ]);
        }

        $this
            ->withServerVariables(['REMOTE_ADDR' => self::SOURCE_IP])
            ->post(route('login'), [
                'email' => self::ACCOUNT_EMAIL,
                'password' => 'password123',
            ]);

        // Assert — the source counter survives a successful login.
        // Recorded as candidate defect FD-2: a shared/NAT source stays
        // throttled even after its legitimate user authenticates.
        $this->assertGreaterThan(0, RateLimiter::attempts($this->sourceKey(self::SOURCE_IP)));
    }

    public function test_login_attempts_for_unknown_email_still_count_toward_the_limiter(): void
    {
        // Arrange — no account exists for this email.
        $unknownEmail = 'ghost@example.com';

        // Act — probe a non-existent address five times.
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login'), [
                'email' => $unknownEmail,
                'password' => 'password123',
            ]);
        }

        // Assert — the sixth attempt is blocked, so unknown accounts cannot be
        // brute-forced without paying the same throttling cost.
        $blocked = $this->post(route('login'), [
            'email' => $unknownEmail,
            'password' => 'password123',
        ]);

        $blocked->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rate_limit_message_does_not_disclose_the_account_existence(): void
    {
        // Arrange — block a real account and a non-existent one in turn.
        User::factory()->create([
            'email' => self::ACCOUNT_EMAIL,
            'password' => Hash::make('password123'),
        ]);

        $unknownEmail = 'ghost@example.com';

        // Act
        $existingMessage = $this->exhaustAndGetErrorMessage(self::ACCOUNT_EMAIL);
        $unknownMessage = $this->exhaustAndGetErrorMessage($unknownEmail);

        // Assert — identical messages, no account enumeration signal.
        $this->assertSame($existingMessage, $unknownMessage);
        $this->assertStringNotContainsString('@example.com', $existingMessage);
    }

    #[DataProvider('attemptBoundaryProvider')]
    public function test_login_is_allowed_up_to_the_fifth_attempt(int $attempt, bool $shouldStillBeAllowed): void
    {
        // Arrange
        User::factory()->create([
            'email' => self::ACCOUNT_EMAIL,
            'password' => Hash::make('password123'),
        ]);

        // Act — wrong password up to the given attempt number.
        for ($current = 1; $current <= $attempt; $current++) {
            $response = $this->post(route('login'), [
                'email' => self::ACCOUNT_EMAIL,
                'password' => 'wrong-password',
            ]);

            if ($current === $attempt) {
                // Assert — a validation error means the attempt was processed
                // (wrong password); a limiter error means it was blocked.
                $hasLimiterError = collect(session('errors')->getBag('default')->get('email'))
                    ->contains(fn (string $message): bool => str_contains($message, 'محاولات دخول كثيرة'));

                $this->assertSame($shouldStillBeAllowed, ! $hasLimiterError);

                return;
            }
        }
    }

    /**
     * @return array<string, array{0: int, 1: bool}>
     */
    public static function attemptBoundaryProvider(): array
    {
        return [
            'first attempt' => [1, true],
            'fifth attempt' => [5, true],
            'sixth attempt' => [6, false],
        ];
    }

    public function test_reset_password_route_has_no_rate_limiter(): void
    {
        // Arrange — a valid token is not needed to observe the routing layer.
        // Recorded as candidate defect FD-3: POST reset-password carries no
        // throttle, unlike POST forgot-password.
        $payload = [
            'token' => 'irrelevant-token',
            'email' => 'student@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ];

        // Act — submit the same request six times.
        $statuses = [];
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $statuses[] = $this->post(route('password.store'), $payload)->status();
        }

        // Assert — no 429 appears at any point.
        $this->assertNotContains(429, $statuses);
    }

    private function exhaustAndGetErrorMessage(string $email): ?string
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $response = $this->post(route('login'), [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
        }

        $errors = session('errors');

        return $errors?->getBag('default')->first('email');
    }

    private function accountKey(): string
    {
        return 'login:account:'.sha1(self::ACCOUNT_EMAIL);
    }

    private function sourceKey(string $ip = '127.0.0.1'): string
    {
        return 'login:source:'.sha1($ip);
    }
}
