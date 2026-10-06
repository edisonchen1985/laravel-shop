<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_form(): void
    {
        // Arrange
        $this->assertGuest();

        // Act
        $response = $this->get(route('login'));

        // Assert
        $response->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('name="password"', false);
    }

    public function test_guest_can_log_in_and_is_redirected_to_the_intended_page(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->assertGuest();
        $this->get(route('cart.index'))->assertRedirect(route('login'));

        // Act
        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Assert
        $response->assertRedirect(route('cart.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_log_user_in(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->assertGuest();

        // Act
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        // Assert
        $response->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_when_opening_a_protected_page(): void
    {
        // Arrange
        $this->assertGuest();

        // Act
        $response = $this->get(route('orders.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_log_out(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->post(route('logout'));

        // Assert
        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
