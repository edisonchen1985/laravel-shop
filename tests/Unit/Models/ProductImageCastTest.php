<?php

namespace Tests\Unit\Models;

use App\Models\ProductImage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductImageCastTest extends TestCase
{
    #[DataProvider('booleanProvider')]
    public function test_is_primary_is_cast_to_a_boolean(
        mixed $input,
        bool $expected
    ): void {
        // Arrange
        $productImage = new ProductImage;

        // Act
        $productImage->is_primary = $input;
        $isPrimary = $productImage->is_primary;

        // Assert
        $this->assertSame($expected, $isPrimary);
    }

    public static function booleanProvider(): array
    {
        return [
            ['1', true],
            [1, true],
            [true, true],
            ['0', false],
            [0, false],
            [false, false],
        ];
    }

    public function test_sort_order_is_cast_to_an_integer(): void
    {
        // Arrange
        $productImage = new ProductImage;

        // Act
        $productImage->sort_order = '2';
        $sortOrder = $productImage->sort_order;

        // Assert
        $this->assertSame(2, $sortOrder);
    }
}