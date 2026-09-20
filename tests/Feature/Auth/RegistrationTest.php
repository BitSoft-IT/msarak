<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'طالب تجريبي',
            'email' => '  Student@Example.COM ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertAuthenticated();

        $user = User::where('email', 'student@example.com')->firstOrFail();

        $this->assertSame('student', $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $response = $this->post(route('register'), [
            'name' => 'اسم آخر',
            'email' => 'TAKEN@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->assertSame(
            1,
            User::where('email', 'taken@example.com')->count()
        );
    }

    public function test_invalid_registration_data_is_rejected(): void
    {
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
        ]);

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_password_is_stored_hashed_not_plaintext(): void
    {
        $this->post(route('register'), [
            'name' => 'طالب',
            'email' => 'hash@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $storedPassword = User::where(
            'email',
            'hash@example.com'
        )->value('password');

        $this->assertNotSame(
            'password123',
            $storedPassword
        );

        $this->assertTrue(
            Hash::check('password123', $storedPassword)
        );
    }
}
