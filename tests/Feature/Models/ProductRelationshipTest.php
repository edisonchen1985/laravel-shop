<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_returns_its_category(): void
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

        // Act
        $productCategory = $product->category;

        // Assert
        $this->assertTrue($productCategory->is($category));
    }

    public function test_product_returns_its_images(): void
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
        $image = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/canvas-bag.jpg',
        ]);

        // Act
        $images = $product->images;

        // Assert
        $this->assertCount(1, $images);
        $this->assertTrue($images->contains(fn (ProductImage $productImage): bool => $productImage->is($image)));
    }
}
