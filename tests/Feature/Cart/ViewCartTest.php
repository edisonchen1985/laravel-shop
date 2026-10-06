<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_see_their_cart_products(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Act
        $response = $this->get(route('cart.index'));

        // Assert
        $response->assertOk()
            ->assertViewIs('cart.index')
            ->assertSeeText($product->name)
            ->assertSeeText('Quantity: 2');
    }

    public function test_user_without_a_cart_sees_empty_state_and_no_cart_is_created(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('cart.index'));

        // Assert
        $response->assertOk()
            ->assertSeeText('Your cart is empty.');
        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
    }

    public function test_existing_empty_cart_shows_empty_state(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        Cart::create(['user_id' => $user->id]);

        // Act
        $response = $this->get(route('cart.index'));

        // Assert
        $response->assertOk()
            ->assertSeeText('Your cart is empty.');
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_soft_deleted_product_is_shown_as_unavailable(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $product->delete();

        // Act
        $response = $this->get(route('cart.index'));

        // Assert
        $response->assertOk()
            ->assertSeeText($product->name)
            ->assertSeeText('Unavailable');
    }

    public function test_inactive_product_is_shown_as_unavailable(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct(['status' => 'inactive']);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Act
        $response = $this->get(route('cart.index'));

        // Assert
        $response->assertOk()
            ->assertSeeText($product->name)
            ->assertSeeText('Unavailable');
    }

    public function test_guest_cannot_view_cart(): void
    {
        // Arrange
        $this->assertGuest();

        // Act
        $response = $this->getJson(route('cart.index'));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_cart_items_and_products_are_eager_loaded(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->createProduct();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Act
        $response = $this->get(route('cart.index'));

        // Assert
        $response->assertOk();
        $loadedCart = $response->viewData('cart');

        $this->assertTrue($loadedCart->relationLoaded('items'));
        $this->assertTrue($loadedCart->items->first()->relationLoaded('product'));
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
