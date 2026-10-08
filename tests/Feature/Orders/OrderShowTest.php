<?php

namespace Tests\Feature\Orders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_owner_can_view_order_and_item_snapshots(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = $this->createOrder($user, 'ORD-DETAIL-001');
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Historical Canvas Bag',
            'sku' => 'BAG-HISTORY',
            'unit_price' => '19.90',
            'quantity' => 2,
            'line_total' => '39.80',
        ]);

        // Act
        $response = $this->get(route('orders.show', $order));

        // Assert
        $response->assertOk()
            ->assertViewIs('orders.show')
            ->assertSeeText('ORD-DETAIL-001')
            ->assertSeeText('Historical Canvas Bag')
            ->assertSeeText('BAG-HISTORY')
            ->assertSeeText('Unit price: 19.90')
            ->assertSeeText('Quantity: 2')
            ->assertSeeText('Line total: 39.80');
    }

    public function test_order_detail_keeps_item_snapshot_after_product_changes_and_soft_delete(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create([
            'name' => 'Snapshot Category',
            'slug' => 'snapshot-category',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Original Product Name',
            'slug' => 'original-product-name',
            'sku' => 'ORIGINAL-SKU',
            'price' => '19.90',
            'stock_quantity' => 4,
            'status' => 'active',
        ]);
        $order = $this->createOrder($user, 'ORD-SNAPSHOT-STABLE');
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Original Product Name',
            'sku' => 'ORIGINAL-SKU',
            'unit_price' => '19.90',
            'quantity' => 2,
            'line_total' => '39.80',
        ]);
        $product->update([
            'name' => 'Changed Product Name',
            'sku' => 'CHANGED-SKU',
            'price' => '99.00',
        ]);
        $product->delete();

        // Act
        $response = $this->get(route('orders.show', $order));

        // Assert
        $response->assertOk()
            ->assertSeeText('Original Product Name')
            ->assertSeeText('ORIGINAL-SKU')
            ->assertSeeText('Unit price: 19.90')
            ->assertSeeText('Line total: 39.80')
            ->assertDontSeeText('Changed Product Name')
            ->assertDontSeeText('CHANGED-SKU')
            ->assertDontSeeText('99.00');
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();
        $order = $this->createOrder($owner, 'ORD-PRIVATE-001');
        $this->actingAs($anotherUser);

        // Act
        $response = $this->get(route('orders.show', $order));

        // Assert
        $response->assertForbidden();
    }

    public function test_nonexistent_order_returns_not_found(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('orders.show', 999999));

        // Assert
        $response->assertNotFound();
    }

    public function test_order_items_are_eager_loaded_after_authorization(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = $this->createOrder($user, 'ORD-DETAIL-LOAD');
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Historical Item',
            'unit_price' => '5.00',
            'quantity' => 1,
            'line_total' => '5.00',
        ]);

        // Act
        $response = $this->get(route('orders.show', $order));

        // Assert
        $response->assertOk();
        $loadedOrder = $response->viewData('order');

        $this->assertTrue($loadedOrder->relationLoaded('items'));
    }

    public function test_guest_cannot_view_order_detail(): void
    {
        // Arrange
        $order = $this->createOrder(User::factory()->create(), 'ORD-GUEST-001');
        $this->assertGuest();

        // Act
        $response = $this->getJson(route('orders.show', $order));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_order_with_null_user_id_is_not_visible_to_another_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = $this->createOrder(null, 'ORD-ORPHAN-001');
        $this->actingAs($user);

        // Act
        $response = $this->get(route('orders.show', $order));

        // Assert
        $response->assertForbidden();
    }

    private function createOrder(?User $user, string $orderNumber): Order
    {
        return Order::create([
            'user_id' => $user?->id,
            'order_number' => $orderNumber,
            'status' => 'pending',
            'customer_name' => $user?->name ?? 'Deleted Customer',
            'customer_email' => $user?->email ?? 'deleted@example.com',
            'subtotal' => '39.80',
            'total' => '39.80',
            'placed_at' => now(),
        ]);
    }
}
