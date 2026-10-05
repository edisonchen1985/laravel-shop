<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddCartItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_a_product_to_their_cart(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 5]);

        // Act
        $response = $this->from('/products/canvas-bag')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Assert
        $response->assertRedirect('/products/canvas-bag')
            ->assertSessionHas('success');

        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_the_same_product_twice_accumulates_quantity_in_one_cart_item(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 5]);

        // Act
        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $response = $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // Assert
        $response->assertRedirect();
        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_quantity_must_be_at_least_one(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct();

        // Act
        $response = $this->from('/products/canvas-bag')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        // Assert
        $response->assertRedirect('/products/canvas-bag')
            ->assertSessionHasErrors('quantity');
        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
    }

    public function test_quantity_greater_than_stock_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 2]);

        // Act
        $response = $this->from('/products/canvas-bag')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // Assert
        $response->assertRedirect('/products/canvas-bag')
            ->assertSessionHasErrors('quantity');
        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    public function test_inactive_product_cannot_be_added(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['status' => 'inactive']);

        // Act
        $response = $this->from('/products/canvas-bag')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Assert
        $response->assertRedirect('/products/canvas-bag')
            ->assertSessionHasErrors('product_id');
        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    public function test_soft_deleted_product_cannot_be_added(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct();
        $product->delete();

        // Act
        $response = $this->from('/products/canvas-bag')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Assert
        $response->assertRedirect('/products/canvas-bag')
            ->assertSessionHasErrors('product_id');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    public function test_adding_to_cart_does_not_decrease_product_stock(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 5]);

        // Act
        $response = $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 5,
        ]);
    }

    public function test_unauthenticated_user_cannot_add_a_product_to_a_cart(): void
    {
        // Arrange
        $product = $this->createProduct();

        // Act
        // JSON content negotiation avoids redirecting to the login route, which this project does not yet define.
        $response = $this->withHeader('Accept', 'application/json')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Assert
        $response->assertUnauthorized();
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_adding_more_than_remaining_stock_keeps_existing_quantity_unchanged(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 5]);
        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();

        // Act
        $response = $this->from('/products/canvas-bag')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // Assert
        $response->assertRedirect('/products/canvas-bag')
            ->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_nonexistent_product_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->from('/products/canvas-bag')->post(route('cart.items.store'), [
            'product_id' => 999999,
            'quantity' => 1,
        ]);

        // Assert
        $response->assertRedirect('/products/canvas-bag')
            ->assertSessionHasErrors('product_id');
        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
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
