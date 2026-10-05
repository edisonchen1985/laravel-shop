<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductCastTest extends TestCase
{
    public function test_price_is_cast_to_a_two_decimal_string(): void
    {
        // Arrange
        $product = new Product;
        $product->price = 19.90;

        // Act
        $price = $product->price;

        // Assert
        $this->assertSame('19.90', $price);
    }

    public function test_stock_quantity_is_cast_to_an_integer(): void
    {
        // Arrange
        $product = new Product;
        $product->stock_quantity = '5';

        // Act
        $stockQuantity = $product->stock_quantity;

        // Assert
        $this->assertSame(5, $stockQuantity);
    }
}
