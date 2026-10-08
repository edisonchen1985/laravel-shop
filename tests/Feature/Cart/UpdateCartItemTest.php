<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCartItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_their_cart_item_quantity(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 8]);
        $cartItem = $this->createCartItem($user, $product, 2);

        // Act
        $response = $this->from('/cart')->patch(route('cart.items.update', $cartItem), [
            'quantity' => 4,
        ]);

        // Assert
        $response->assertRedirect('/cart')
            ->assertSessionHas('success');
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 4,
        ]);
    }

    public function test_update_sets_quantity_instead_of_accumulating_it(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 10]);
        $cartItem = $this->createCartItem($user, $product, 2);

        // Act
        $this->patch(route('cart.items.update', $cartItem), ['quantity' => 3]);

        // Assert
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 3,
        ]);
    }

    public function test_quantity_is_required(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $cartItem = $this->createCartItem($user, $this->createProduct(), 2);

        // Act
        $response = $this->from('/cart')->patch(route('cart.items.update', $cartItem), []);

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('quantity');
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_quantity_must_be_an_integer(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $cartItem = $this->createCartItem($user, $this->createProduct(), 2);

        // Act
        $response = $this->from('/cart')->patch(route('cart.items.update', $cartItem), [
            'quantity' => 'many',
        ]);

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('quantity');
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_quantity_must_be_at_least_one(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $cartItem = $this->createCartItem($user, $this->createProduct(), 2);

        // Act
        $response = $this->from('/cart')->patch(route('cart.items.update', $cartItem), [
            'quantity' => 0,
        ]);

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('quantity');
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_quantity_over_stock_is_rejected_without_changing_existing_quantity(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 3]);
        $cartItem = $this->createCartItem($user, $product, 2);

        // Act
        $response = $this->from('/cart')->patch(route('cart.items.update', $cartItem), [
            'quantity' => 4,
        ]);

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('quantity');
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_inactive_product_cannot_be_updated_and_quantity_stays_unchanged(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['status' => 'inactive']);
        $cartItem = $this->createCartItem($user, $product, 2);

        // Act
        $response = $this->from('/cart')->patch(route('cart.items.update', $cartItem), [
            'quantity' => 3,
        ]);

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('product_id');
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_product_from_inactive_category_cannot_be_updated(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct();
        $cartItem = $this->createCartItem($user, $product, 2);
        $product->category()->update(['is_active' => false]);

        // Act
        $response = $this->from('/cart')->patch(route('cart.items.update', $cartItem), [
            'quantity' => 3,
        ]);

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('product_id');
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_soft_deleted_product_cannot_be_updated_and_quantity_stays_unchanged(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct();
        $cartItem = $this->createCartItem($user, $product, 2);
        $product->delete();

        // Act
        $response = $this->from('/cart')->patch(route('cart.items.update', $cartItem), [
            'quantity' => 3,
        ]);

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('product_id');
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_user_cannot_update_another_users_cart_item(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->createProduct();
        $cartItem = $this->createCartItem($owner, $product, 2);
        $this->actingAs($otherUser);

        // Act
        $response = $this->patch(route('cart.items.update', $cartItem), ['quantity' => 3]);

        // Assert
        $response->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_guest_cannot_update_a_cart_item(): void
    {
        // Arrange
        $product = $this->createProduct();
        $user = User::factory()->create();
        $cartItem = $this->createCartItem($user, $product, 2);
        $this->assertGuest();

        // Act
        $response = $this->patchJson(route('cart.items.update', $cartItem), ['quantity' => 3]);

        // Assert
        $response->assertUnauthorized();
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_updating_cart_item_does_not_decrease_product_stock(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 8]);
        $cartItem = $this->createCartItem($user, $product, 2);

        // Act
        $response = $this->patch(route('cart.items.update', $cartItem), ['quantity' => 5]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 8,
        ]);
    }

    private function createCartItem(User $user, Product $product, int $quantity): CartItem
    {
        $cart = Cart::create(['user_id' => $user->id]);

        return CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'price' => '19.90',
            'stock_quantity' => 10,
            'status' => 'active',
        ], $attributes));
    }
}
