<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteCartItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_their_cart_item(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct();
        $cartItem = $this->createCartItem($user, $product);

        // Act
        $response = $this->from('/cart')->delete(route('cart.items.destroy', $cartItem));

        // Assert
        $response->assertRedirect('/cart')
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_user_cannot_delete_another_users_cart_item(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $cartItem = $this->createCartItem($owner, $this->createProduct());
        $this->actingAs($otherUser);

        // Act
        $response = $this->delete(route('cart.items.destroy', $cartItem));

        // Assert
        $response->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id]);
    }

    public function test_guest_cannot_delete_a_cart_item(): void
    {
        // Arrange
        $cartItem = $this->createCartItem(User::factory()->create(), $this->createProduct());
        $this->assertGuest();

        // Act
        $response = $this->deleteJson(route('cart.items.destroy', $cartItem));

        // Assert
        $response->assertUnauthorized();
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id]);
    }

    public function test_nonexistent_cart_item_returns_not_found(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        Cart::create(['user_id' => $user->id]);

        // Act
        $response = $this->delete(route('cart.items.destroy', 999999));

        // Assert
        $response->assertNotFound();
    }

    private function createCartItem(User $user, Product $product): CartItem
    {
        $cart = Cart::create(['user_id' => $user->id]);

        return CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    private function createProduct(): Product
    {
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'price' => '19.90',
            'stock_quantity' => 10,
            'status' => 'active',
        ]);
    }
}
