<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * CSRF middleware with the PHPUnit bypass disabled.
 *
 * VerifyCsrfToken::runningUnitTests() returns true under PHPUnit, which would
 * skip token verification entirely. This subclass turns off only that bypass,
 * leaving the real token-matching logic available for testing.
 */
class TestingCsrfMiddleware extends VerifyCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}

/**
 * Q-02 (Phase 1) — Session lifecycle and route-protection coverage.
 *
 * Targets session fixation, session invalidation after logout, the generic
 * failure message for unknown accounts, guest/auth route guards and CSRF
 * enforcement. Production code is never modified by these tests.
 */
class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_regenerates_the_session_id(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->startSession();
        $beforeLogin = Session::getId();

        // Act
        $response = $this->post(route('login'), [
            'email' => 'student@example.com',
            'password' => 'password123',
        ]);

        // Assert — a stolen pre-login session id must not stay valid.
        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($beforeLogin, Session::getId());
    }

    public function test_unknown_email_returns_the_same_message_as_wrong_password(): void
    {
        // Arrange — only an existing account is seeded.
        User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
        ]);

        $unknownEmail = 'ghost@example.com';

        // Act — probe a non-existent account, then a wrong password.
        $unknownResponse = $this->post(route('login'), [
            'email' => $unknownEmail,
            'password' => 'password123',
        ]);

        $wrongPasswordResponse = $this->post(route('login'), [
            'email' => 'student@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert — identical generic message, so the account cannot be enumerated.
        $unknownResponse->assertSessionHasErrors([
            'email' => 'بيانات الدخول غير صحيحة.',
        ]);

        $wrongPasswordResponse->assertSessionHasErrors([
            'email' => 'بيانات الدخول غير صحيحة.',
        ]);

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_session_and_clears_its_data(): void
    {
        // Arrange
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->startSession();
        Session::put('sensitive_marker', 'keep-me-out');

        $beforeLogout = Session::getId();

        // Act
        $response = $this->post(route('logout'));

        // Assert — new id, flushed payload, no authenticated user.
        $response->assertRedirect(route('home'));

        $this->assertNotSame($beforeLogout, Session::getId());
        $this->assertFalse(Session::has('sensitive_marker'));
        $this->assertGuest();
    }

    public function test_old_session_id_does_not_reauthenticate_after_logout(): void
    {
        // Arrange — authenticate and capture the live session id.
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->startSession();

        $stolenSessionId = Session::getId();

        // Act — log out, then attempt to replay the previous session id.
        $this->post(route('logout'));

        Session::setId($stolenSessionId);

        // Assert — the replayed id carries no authenticated identity.
        $this->assertFalse(Auth::check());
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_guest_routes(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert — guest-only pages must reject a logged-in visitor.
        $this->actingAs($user)->get(route('login'))->assertRedirect(route('home'));
        $this->actingAs($user)->get(route('register'))->assertRedirect(route('home'));
        $this->actingAs($user)->get(route('password.request'))->assertRedirect(route('home'));
    }

    public function test_guest_is_redirected_to_login_on_the_protected_route(): void
    {
        // Act — no authenticated user.
        $response = $this->post(route('logout'));

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_form_contains_a_csrf_token_field(): void
    {
        // Act
        $response = $this->get(route('login'));

        // Assert — the form must render the token for the browser to submit it.
        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);
    }

    public function test_post_request_without_csrf_token_is_rejected(): void
    {
        // Arrange
        $this->startSession();

        $middleware = new TestingCsrfMiddleware($this->app, $this->app['encrypter']);

        $request = Request::create(route('login'), 'POST', [
            'email' => 'student@example.com',
            'password' => 'password123',
        ]);

        $request->setLaravelSession(Session::driver());

        // Act & Assert — a request without a token must not pass through.
        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => response('should-not-reach-here'));
    }

    public function test_post_request_with_a_valid_csrf_token_passes_through(): void
    {
        // Arrange — same middleware, but the request carries the session token.
        $this->startSession();

        $middleware = new TestingCsrfMiddleware($this->app, $this->app['encrypter']);

        $request = Request::create(route('login'), 'POST', [
            'email' => 'student@example.com',
            'password' => 'password123',
            '_token' => Session::token(),
        ]);

        $request->setLaravelSession(Session::driver());

        // Act
        $response = $middleware->handle($request, fn () => response('passed-through'));

        // Assert
        $this->assertSame('passed-through', $response->getContent());
    }
}
