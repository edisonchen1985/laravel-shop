<?php

namespace Tests\Feature\Models;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCartRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_returns_its_cart(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::create([
            'user_id' => $user->id,
        ]);

        // Act
        $userCart = $user->cart;

        // Assert
        $this->assertTrue($userCart->is($cart));
    }

    public function test_cart_returns_its_user(): void
    {
        //Arrange
        $user = User::factory()->create();
        $cart = Cart::create([
            'user_id' => $user->id
        ]);
        $cartUser = $cart->user;
        $this->assertTrue($cartUser->is($user));
    }

    public function test_user_returns_null_when_it_has_no_cart(): void
    {
        $user = User::factory()->create();
        $userCart = $user->cart;
        $this->assertNull($userCart);
    }
}
