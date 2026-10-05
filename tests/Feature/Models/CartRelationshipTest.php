<?php

namespace Tests\Feature\Models;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_returns_its_items(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::create([
            'user_id' => $user->id,
        ]);
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'price' => '19.90',
        ]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Act
        $items = $cart->items;

        // Assert
        $this->assertCount(1, $items);
        $this->assertTrue($items->contains(fn (CartItem $cartItem): bool => $cartItem->is($item)));
    }
}
