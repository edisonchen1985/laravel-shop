<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_orders_and_order_item_snapshots(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = $this->createOrder($user, 'ORD-OWN-001');
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Snapshot Canvas Bag',
            'sku' => 'BAG-SNAPSHOT',
            'unit_price' => '19.90',
            'quantity' => 2,
            'line_total' => '39.80',
        ]);

        // Act
        $response = $this->get(route('orders.index'));

        // Assert
        $response->assertOk()
            ->assertViewIs('orders.index')
            ->assertSeeText('ORD-OWN-001')
            ->assertSeeText('Snapshot Canvas Bag')
            ->assertSeeText('BAG-SNAPSHOT')
            ->assertSeeText('Quantity: 2')
            ->assertSeeText('39.80');
    }

    public function test_user_cannot_see_another_users_orders(): void
    {
        // Arrange
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $this->actingAs($user);
        $this->createOrder($user, 'ORD-MY-001');
        $this->createOrder($anotherUser, 'ORD-OTHER-001');

        // Act
        $response = $this->get(route('orders.index'));

        // Assert
        $response->assertOk()
            ->assertSeeText('ORD-MY-001')
            ->assertDontSeeText('ORD-OTHER-001');
    }

    public function test_user_with_no_orders_sees_empty_state(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('orders.index'));

        // Assert
        $response->assertOk()->assertSeeText('You have no orders yet.');
    }

    public function test_order_items_are_eager_loaded(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = $this->createOrder($user, 'ORD-LOAD-001');
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Historical Product',
            'unit_price' => '5.00',
            'quantity' => 1,
            'line_total' => '5.00',
        ]);

        // Act
        $response = $this->get(route('orders.index'));

        // Assert
        $response->assertOk();
        $orders = $response->viewData('orders');

        $this->assertTrue($orders->first()->relationLoaded('items'));
    }

    public function test_guest_cannot_view_orders(): void
    {
        // Arrange
        $this->assertGuest();

        // Act
        $response = $this->getJson(route('orders.index'));

        // Assert
        $response->assertUnauthorized();
    }

    private function createOrder(User $user, string $orderNumber): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
            'status' => 'pending',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'subtotal' => '39.80',
            'total' => '39.80',
            'placed_at' => now(),
        ]);
    }
}
