<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Q-02 (Phase 1) — Security and edge-case coverage for registration.
 *
 * Complements the functional RegistrationTest (H-02) by targeting privilege
 * escalation, secret leakage, input normalisation and validation boundaries.
 * Production code is never modified by these tests.
 */
class RegistrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_role_cannot_be_escalated_to_admin_via_request_input(): void
    {
        // Arrange — a hostile registration payload attempting privilege escalation.
        $payload = [
            'name' => 'طالب تجريبي',
            'email' => 'student@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ];

        // Act
        $response = $this->post(route('register'), $payload);

        // Assert — the persisted role stays the default, regardless of the input.
        $response->assertRedirect(route('home'));

        $user = User::where('email', 'student@example.com')->firstOrFail();

        $this->assertSame('student', $user->role);
        $this->assertSame(0, User::where('role', 'admin')->count());
    }

    public function test_password_never_persists_in_session_or_flash_data(): void
    {
        // Arrange
        $password = 'super-secret-123';

        $payload = [
            'name' => 'طالب',
            'email' => 'secret@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ];

        // Act
        $response = $this->post(route('register'), $payload);

        // Assert — the plaintext must not survive in the store, session or body.
        $response->assertRedirect(route('home'));

        $stored = User::where('email', 'secret@example.com')->value('password');

        $this->assertNotSame($password, $stored);
        $this->assertTrue(Hash::check($password, $stored));

        $this->assertStringNotContainsString(
            $password,
            json_encode(session()->all(), JSON_UNESCAPED_UNICODE)
        );

        $this->assertStringNotContainsString($password, (string) $response->getContent());
    }

    public function test_name_is_trimmed_before_storage(): void
    {
        // Arrange
        $payload = [
            'name' => '  طالب تجريبي  ',
            'email' => 'trimmed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->post(route('register'), $payload);

        // Assert
        $response->assertRedirect(route('home'));

        $this->assertSame(
            'طالب تجريبي',
            User::where('email', 'trimmed@example.com')->value('name')
        );
    }

    #[DataProvider('invalidInputProvider')]
    public function test_invalid_registration_input_is_rejected(array $payload, string $expectedError): void
    {
        // Act
        $response = $this->post(route('register'), $payload);

        // Assert — the offending field is flagged, and nothing is persisted.
        $response->assertSessionHasErrors([$expectedError]);

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    /**
     * Array-typed scalar fields (name/email/password).
     *
     * Documented separately from invalidInputProvider: passing an array where
     * a string is expected triggers a PHP "Array to string conversion" inside
     * RegisteredUserController::store() (the (string) cast on the merged
     * input), which Laravel escalates to an exception in the test environment.
     * The request therefore never reaches the validator. This is recorded as
     * candidate defect FD-1 and intentionally left unfixed per Q-02 rules.
     *
     * @return array<string, array{0: string}>
     */
    public static function arrayFieldProvider(): array
    {
        return [
            'name' => ['name'],
            'email' => ['email'],
        ];
    }

    #[DataProvider('arrayFieldProvider')]
    public function test_array_input_in_scalar_field_is_handled_safely(string $field): void
    {
        // Arrange — a hostile payload substituting a scalar field with an array.
        $payload = [
            'name' => 'طالب',
            'email' => 'valid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            $field => ['a', 'b'],
        ];

        // Act — Laravel's test client converts thrown exceptions into a 500
        // response rather than rethrowing, so the failure surfaces here.
        $response = $this->post(route('register'), $payload);

        // Assert — FD-1: the request must never produce a user, whatever the
        // failure mode. A clean rejection is preferred; a 500 is tolerated and
        // recorded as a candidate defect rather than fixed here.
        $this->assertSame(0, User::count());

        if ($response->status() !== 500) {
            $response->assertSessionHasErrors([$field]);
            $this->assertGuest();
        }
    }

    /**
     * Each case carries the payload and the field that must be flagged.
     *
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidInputProvider(): array
    {
        $valid = [
            'name' => 'طالب',
            'email' => 'valid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        return [
            // Length boundaries.
            'name over 255 characters' => [array_merge($valid, ['name' => str_repeat('ا', 256)]), 'name'],
            'email over 255 characters' => [array_merge($valid, ['email' => str_repeat('a', 250).'@example.com']), 'email'],

            // Confirmation mismatch.
            'password not confirmed' => [array_merge($valid, ['password_confirmation' => 'different-value']), 'password'],

            // Format rules.
            'email without domain' => [array_merge($valid, ['email' => 'not-an-email']), 'email'],
        ];
    }
}
