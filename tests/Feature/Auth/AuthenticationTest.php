<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        RateLimiter::clear('login:test');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => '  Student@Example.COM ',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected_with_generic_message(): void
    {
        User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'student@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'بيانات الدخول غير صحيحة.',
        ]);

        $this->assertGuest();
    }

    public function test_account_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login'), [
                'email' => 'student@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login'), [
            'email' => 'student@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_source_is_rate_limited_across_different_accounts(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this
                ->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
                ->post(route('login'), [
                    'email' => "unknown{$attempt}@example.com",
                    'password' => 'wrong-password',
                ]);
        }

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password123',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_successful_login_clears_account_limiter(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_guest_cannot_access_logout_route(): void
    {
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
