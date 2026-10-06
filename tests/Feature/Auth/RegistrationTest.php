<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_registration_form(): void
    {
        // Arrange
        $this->assertGuest();

        // Act
        $response = $this->get(route('register'));

        // Assert
        $response->assertOk()
            ->assertViewIs('auth.register')
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_guest_can_register_and_is_logged_in(): void
    {
        // Arrange
        $this->assertGuest();

        // Act
        $response = $this->post(route('register.store'), [
            'name' => 'Morgan Customer',
            'email' => 'morgan@example.com',
            'phone' => '555-0100',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ]);

        // Assert
        $response->assertRedirect(route('cart.index'));
        $user = User::query()->where('email', 'morgan@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Morgan Customer', $user->name);
        $this->assertSame('555-0100', $user->phone);
        $this->assertTrue(Hash::check('secure-password-123', $user->password));
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        // Act
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ]);

        // Assert
        $response->assertRedirect(route('register'))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        // Arrange
        $this->assertGuest();

        // Act
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Morgan Customer',
            'email' => 'morgan@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'different-password',
        ]);

        // Assert
        $response->assertRedirect(route('register'))->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }
}
