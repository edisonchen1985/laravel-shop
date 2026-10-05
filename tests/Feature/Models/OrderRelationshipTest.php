<?php

namespace Tests\Feature\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_returns_its_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORDER-1001',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'subtotal' => '19.90',
            'total' => '19.90',
        ]);

        // Act
        $orderUser = $order->user;

        // Assert
        $this->assertTrue($orderUser->is($user));
    }

    public function test_order_returns_its_items(): void
    {
        // Arrange
        $order = Order::create([
            'order_number' => 'ORDER-1002',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'subtotal' => '19.90',
            'total' => '19.90',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Canvas Bag',
            'sku' => 'BAG-001',
            'unit_price' => '19.90',
            'quantity' => 1,
            'line_total' => '19.90',
        ]);

        // Act
        $items = $order->items;

        // Assert
        $this->assertCount(1, $items);
        $this->assertTrue($items->contains(fn (OrderItem $orderItem): bool => $orderItem->is($item)));
    }

    public function test_order_user_is_null_when_it_has_no_user(): void
    {
        // Arrange
        $order = Order::create([
            'order_number' => 'ORDER-1003',
            'customer_name' => 'Guest Customer',
            'customer_email' => 'guest@example.com',
            'subtotal' => '0.00',
            'total' => '0.00',
        ]);

        // Act
        $orderUser = $order->user;

        // Assert
        $this->assertNull($orderUser);
    }
}
