<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_item_returns_its_product(): void
    {
        // Arrange
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
        $order = Order::create([
            'order_number' => 'ORDER-2001',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'subtotal' => '19.90',
            'total' => '19.90',
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => 'BAG-001',
            'unit_price' => '19.90',
            'quantity' => 1,
            'line_total' => '19.90',
        ]);

        // Act
        $orderItemProduct = $orderItem->product;

        // Assert
        $this->assertTrue($orderItemProduct->is($product));
    }

    public function test_order_item_product_is_null_when_product_id_is_null(): void
    {
        // Arrange
        $order = Order::create([
            'order_number' => 'ORDER-2002',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'subtotal' => '0.00',
            'total' => '0.00',
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Deleted Product Snapshot',
            'unit_price' => '19.90',
            'quantity' => 1,
            'line_total' => '19.90',
        ]);

        // Act
        $orderItemProduct = $orderItem->product;

        // Assert
        $this->assertNull($orderItemProduct);
    }
}
