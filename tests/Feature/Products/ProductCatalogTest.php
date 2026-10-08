<?php

namespace Tests\Feature\Products;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_active_products_from_active_categories(): void
    {
        // Arrange
        $activeCategory = $this->createCategory('active-category');
        $inactiveCategory = $this->createCategory('inactive-category', false);
        $activeProduct = $this->createProduct($activeCategory, 'active-product');
        $this->createProduct($activeCategory, 'draft-product', ['status' => 'draft']);
        $this->createProduct($inactiveCategory, 'hidden-category-product');
        $deletedProduct = $this->createProduct($activeCategory, 'deleted-product');
        $deletedProduct->delete();

        // Act
        $response = $this->get(route('products.index'));

        // Assert
        $response->assertOk()
            ->assertSeeText($activeProduct->name)
            ->assertDontSeeText('Draft Product')
            ->assertDontSeeText('Hidden Category Product')
            ->assertDontSeeText('Deleted Product')
            ->assertViewIs('products.index');
    }

    public function test_catalog_can_filter_products_by_active_category_slug(): void
    {
        // Arrange
        $selectedCategory = $this->createCategory('selected-category');
        $otherCategory = $this->createCategory('other-category');
        $selectedProduct = $this->createProduct($selectedCategory, 'selected-product');
        $this->createProduct($otherCategory, 'other-product');

        // Act
        $response = $this->get(route('products.index', ['category' => $selectedCategory->slug]));

        // Assert
        $response->assertOk()
            ->assertSeeText($selectedProduct->name)
            ->assertDontSeeText('Other Product');
    }

    public function test_customer_can_view_active_product_details(): void
    {
        // Arrange
        $category = $this->createCategory('accessories');
        $product = $this->createProduct($category, 'canvas-bag', [
            'description' => 'A sturdy everyday bag.',
            'price' => '19.90',
        ]);

        // Act
        $response = $this->get(route('products.show', $product->slug));

        // Assert
        $response->assertOk()
            ->assertViewIs('products.show')
            ->assertSeeText($product->name)
            ->assertSeeText($category->name)
            ->assertSeeText('A sturdy everyday bag.')
            ->assertSeeText('19.90');
    }

    public function test_inactive_product_is_not_publicly_visible(): void
    {
        // Arrange
        $category = $this->createCategory('accessories');
        $product = $this->createProduct($category, 'inactive-product', ['status' => 'inactive']);

        // Act
        $response = $this->get(route('products.show', $product->slug));

        // Assert
        $response->assertNotFound();
    }

    public function test_product_in_inactive_category_is_not_publicly_visible_by_url(): void
    {
        // Arrange
        $category = $this->createCategory('inactive-category', false);
        $product = $this->createProduct($category, 'hidden-product');

        // Act
        $response = $this->get(route('products.show', $product->slug));

        // Assert
        $response->assertNotFound();
    }

    public function test_soft_deleted_product_is_not_publicly_visible(): void
    {
        // Arrange
        $category = $this->createCategory('accessories');
        $product = $this->createProduct($category, 'deleted-product');
        $product->delete();

        // Act
        $response = $this->get(route('products.show', 'deleted-product'));

        // Assert
        $response->assertNotFound();
    }

    private function createCategory(string $slug, bool $isActive = true): Category
    {
        return Category::create([
            'name' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'is_active' => $isActive,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(Category $category, string $slug, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'price' => '10.00',
            'stock_quantity' => 5,
            'status' => 'active',
        ], $attributes));
    }
}
