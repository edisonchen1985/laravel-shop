<?php

namespace Tests\Feature\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
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

    public function test_catalog_paginates_twelve_products_per_page(): void
    {
        // Arrange
        $category = $this->createCategory('pagination-category');
        for ($productNumber = 1; $productNumber <= 13; $productNumber++) {
            $this->createProduct($category, 'pagination-product-'.$productNumber);
        }

        // Act
        $firstPage = $this->get(route('products.index'));
        $secondPage = $this->get(route('products.index', ['page' => 2]));

        // Assert
        $firstPage->assertOk();
        $secondPage->assertOk();
        $this->assertSame(12, $firstPage->viewData('products')->perPage());
        $this->assertSame(2, $secondPage->viewData('products')->currentPage());
        $this->assertCount(12, $firstPage->viewData('products')->items());
        $this->assertCount(1, $secondPage->viewData('products')->items());
    }

    public function test_pagination_keeps_search_and_category_filters(): void
    {
        // Arrange
        $selectedCategory = $this->createCategory('canvas-category');
        $otherCategory = $this->createCategory('other-category');
        for ($productNumber = 1; $productNumber <= 13; $productNumber++) {
            $this->createProduct($selectedCategory, 'canvas-item-'.$productNumber, [
                'name' => 'Canvas Item '.$productNumber,
            ]);
        }
        $this->createProduct($otherCategory, 'canvas-other-category-item', [
            'name' => 'Canvas Other Category Item',
        ]);

        // Act
        $response = $this->get(route('products.index', [
            'category' => $selectedCategory->slug,
            'search' => 'Canvas',
            'page' => 2,
        ]));

        // Assert
        $response->assertOk()
            ->assertSeeText('Canvas Item')
            ->assertDontSeeText('Canvas Other Category Item');
        $products = $response->viewData('products');
        $this->assertSame(2, $products->currentPage());
        $this->assertSame(13, $products->total());
        $this->assertCount(1, $products->items());
        $this->assertSame(
            route('products.index', ['category' => $selectedCategory->slug, 'search' => 'Canvas', 'page' => 1]),
            $products->url(1),
        );
    }

    public function test_catalog_search_matches_product_name(): void
    {
        // Arrange
        $category = $this->createCategory('search-category');
        $matchingProduct = $this->createProduct($category, 'canvas-tote', ['name' => 'Canvas Tote']);
        $this->createProduct($category, 'ceramic-mug', ['name' => 'Ceramic Mug']);
        $this->createProduct($category, 'inactive-canvas', [
            'name' => 'Inactive Canvas',
            'status' => 'inactive',
        ]);

        // Act
        $response = $this->get(route('products.index', ['search' => 'Canvas']));

        // Assert
        $response->assertOk()
            ->assertSeeText($matchingProduct->name)
            ->assertDontSeeText('Ceramic Mug')
            ->assertDontSeeText('Inactive Canvas');
    }

    public function test_catalog_search_can_be_combined_with_category_filter(): void
    {
        // Arrange
        $selectedCategory = $this->createCategory('selected-search-category');
        $otherCategory = $this->createCategory('other-search-category');
        $matchingProduct = $this->createProduct($selectedCategory, 'selected-blue-shirt', ['name' => 'Blue Shirt']);
        $this->createProduct($selectedCategory, 'selected-red-shirt', ['name' => 'Red Shirt']);
        $this->createProduct($otherCategory, 'other-blue-shirt', ['name' => 'Blue Shirt Other Category']);

        // Act
        $response = $this->get(route('products.index', [
            'category' => $selectedCategory->slug,
            'search' => 'Blue',
        ]));

        // Assert
        $response->assertOk()
            ->assertSeeText($matchingProduct->name)
            ->assertDontSeeText('Red Shirt')
            ->assertDontSeeText('Blue Shirt Other Category');
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

    public function test_out_of_stock_product_is_not_listed_and_detail_has_no_add_to_cart_form(): void
    {
        // Arrange
        $category = $this->createCategory('stock-category');
        $product = $this->createProduct($category, 'out-of-stock-product', ['stock_quantity' => 0]);

        // Act
        $catalogResponse = $this->get(route('products.index'));
        $detailResponse = $this->get(route('products.show', $product->slug));

        // Assert
        $catalogResponse->assertOk()->assertDontSeeText($product->name);
        $detailResponse->assertOk()
            ->assertSeeText('Status: Out of stock')
            ->assertDontSee('action="'.route('cart.items.store').'"', false);
    }

    public function test_catalog_and_product_details_show_primary_image_first(): void
    {
        // Arrange
        $category = $this->createCategory('image-category');
        $product = $this->createProduct($category, 'image-product');
        $product->images()->create([
            'path' => 'products/'.$product->id.'/secondary.png',
            'alt_text' => 'Secondary view',
            'sort_order' => 0,
            'is_primary' => false,
        ]);
        $product->images()->create([
            'path' => 'products/'.$product->id.'/primary.png',
            'alt_text' => 'Primary view',
            'sort_order' => 5,
            'is_primary' => true,
        ]);

        // Act
        $catalogResponse = $this->get(route('products.index'));
        $detailResponse = $this->get(route('products.show', $product->slug));

        // Assert
        $catalogResponse->assertOk()->assertSee('products/'.$product->id.'/primary.png');
        $detailResponse->assertOk()
            ->assertSee('products/'.$product->id.'/primary.png')
            ->assertSee('alt="Primary view"', false);
        $this->assertInstanceOf(ProductImage::class, $detailResponse->viewData('product')->images->first());
        $this->assertSame('products/'.$product->id.'/primary.png', $detailResponse->viewData('product')->images->first()->path);
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
