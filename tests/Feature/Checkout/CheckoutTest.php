<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_checkout_and_order_item_keeps_product_snapshots(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Ada Shopper',
            'email' => 'ada@example.com',
        ]);
        $this->actingAs($user);
        $product = $this->createProduct([
            'name' => 'Canvas Bag',
            'sku' => 'BAG-001',
            'price' => '12.35',
            'stock_quantity' => 8,
        ]);
        $cart = $this->createCart($user);
        $this->addCartItem($cart, $product, 2);

        // Act
        $response = $this->from('/cart')->post(route('checkout.store'));

        // Assert
        $response->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('Ada Shopper', $order->customer_name);
        $this->assertSame('ada@example.com', $order->customer_email);
        $this->assertSame('pending', $order->status);
        $this->assertNotNull($order->placed_at);
        $this->assertCount(1, $order->items);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Canvas Bag',
            'sku' => 'BAG-001',
            'unit_price' => '12.35',
            'quantity' => 2,
            'line_total' => '24.70',
        ]);

        $product->update(['name' => 'Renamed Bag', 'sku' => 'BAG-002', 'price' => '99.99']);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'Canvas Bag',
            'sku' => 'BAG-001',
            'unit_price' => '12.35',
        ]);
    }

    public function test_checkout_calculates_decimal_amounts_without_float_rounding(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $cart = $this->createCart($user);
        $firstProduct = $this->createProduct([
            'name' => 'First Product',
            'slug' => 'first-product',
            'price' => '19.90',
        ]);
        $secondProduct = $this->createProduct([
            'name' => 'Second Product',
            'slug' => 'second-product',
            'price' => '5.35',
        ]);
        $this->addCartItem($cart, $firstProduct, 2);
        $this->addCartItem($cart, $secondProduct, 3);

        // Act
        $this->post(route('checkout.store'));

        // Assert
        $order = Order::query()->firstOrFail();
        $this->assertSame('55.85', $order->subtotal);
        $this->assertSame('55.85', $order->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $firstProduct->id,
            'line_total' => '39.80',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $secondProduct->id,
            'line_total' => '16.05',
        ]);
    }

    public function test_checkout_decrements_stock_and_clears_cart_items(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 7]);
        $cart = $this->createCart($user);
        $this->addCartItem($cart, $product, 3);

        // Act
        $response = $this->post(route('checkout.store'));

        // Assert
        $response->assertRedirect(route('cart.index'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 4,
        ]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_insufficient_stock_rejects_checkout_without_partial_changes(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 1]);
        $cart = $this->createCart($user);
        $cartItem = $this->addCartItem($cart, $product, 2);

        // Act
        $response = $this->from('/cart')->post(route('checkout.store'));

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 1]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_inactive_product_rejects_checkout(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['status' => 'inactive']);
        $cart = $this->createCart($user);
        $cartItem = $this->addCartItem($cart, $product, 1);

        // Act
        $response = $this->from('/cart')->post(route('checkout.store'));

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id]);
    }

    public function test_inactive_category_rejects_checkout_and_preserves_cart_and_stock(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['stock_quantity' => 5]);
        $cart = $this->createCart($user);
        $cartItem = $this->addCartItem($cart, $product, 2);
        $product->category()->update(['is_active' => false]);

        // Act
        $response = $this->from('/cart')->post(route('checkout.store'));

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 5]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_soft_deleted_product_rejects_checkout(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct();
        $cart = $this->createCart($user);
        $cartItem = $this->addCartItem($cart, $product, 1);
        $product->delete();

        // Act
        $response = $this->from('/cart')->post(route('checkout.store'));

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id]);
    }

    public function test_empty_cart_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $cart = $this->createCart($user);

        // Act
        $response = $this->from('/cart')->post(route('checkout.store'));

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('carts', ['id' => $cart->id]);
    }

    public function test_checkout_without_a_cart_is_rejected_without_creating_one(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->from('/cart')->post(route('checkout.store'));

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('cart');
        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_cannot_checkout(): void
    {
        // Arrange
        $this->assertGuest();

        // Act
        $response = $this->postJson(route('checkout.store'));

        // Assert
        $response->assertUnauthorized();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_transaction_rolls_back_prior_item_stock_order_and_cart_changes(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $cart = $this->createCart($user);
        $availableProduct = $this->createProduct([
            'name' => 'Available Product',
            'slug' => 'available-product',
            'stock_quantity' => 5,
        ]);
        $shortProduct = $this->createProduct([
            'name' => 'Short Stock Product',
            'slug' => 'short-stock-product',
            'stock_quantity' => 1,
        ]);
        $availableCartItem = $this->addCartItem($cart, $availableProduct, 2);
        $shortCartItem = $this->addCartItem($cart, $shortProduct, 2);

        // Act
        $response = $this->from('/cart')->post(route('checkout.store'));

        // Assert
        $response->assertRedirect('/cart')->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseHas('products', ['id' => $availableProduct->id, 'stock_quantity' => 5]);
        $this->assertDatabaseHas('products', ['id' => $shortProduct->id, 'stock_quantity' => 1]);
        $this->assertDatabaseHas('cart_items', ['id' => $availableCartItem->id, 'quantity' => 2]);
        $this->assertDatabaseHas('cart_items', ['id' => $shortCartItem->id, 'quantity' => 2]);
    }

    private function createCart(User $user): Cart
    {
        return Cart::create(['user_id' => $user->id]);
    }

    private function addCartItem(Cart $cart, Product $product, int $quantity): CartItem
    {
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
        $categoryNumber = Category::query()->count() + 1;
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories-'.$categoryNumber,
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => null,
            'price' => '19.90',
            'stock_quantity' => 10,
            'status' => 'active',
        ], $attributes));
    }
}
