<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_product_image_and_first_image_becomes_primary(): void
    {
        // Arrange
        Storage::fake('public');
        $product = $this->createProduct();
        $this->actingAs($this->adminUser());

        // Act
        $response = $this->post(route('admin.products.images.store', $product), [
            'image' => UploadedFile::fake()->image('front.png'),
            'alt_text' => 'Front view',
            'sort_order' => 2,
        ]);

        // Assert
        $response->assertRedirect(route('admin.products.edit', $product));
        $image = ProductImage::query()->where('product_id', $product->id)->firstOrFail();

        $this->assertSame('Front view', $image->alt_text);
        $this->assertSame(2, $image->sort_order);
        $this->assertTrue($image->is_primary);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_image_upload_rejects_non_image_files(): void
    {
        // Arrange
        Storage::fake('public');
        $product = $this->createProduct();
        $this->actingAs($this->adminUser());

        // Act / Assert
        $this->from(route('admin.products.edit', $product))
            ->post(route('admin.products.images.store', $product), [
                'image' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('product_images', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_admin_can_update_image_details_and_only_one_image_is_primary(): void
    {
        // Arrange
        $product = $this->createProduct();
        $firstImage = $product->images()->create([
            'path' => 'products/'.$product->id.'/first.png',
            'alt_text' => 'First',
            'sort_order' => 0,
            'is_primary' => true,
        ]);
        $secondImage = $product->images()->create([
            'path' => 'products/'.$product->id.'/second.png',
            'alt_text' => 'Second',
            'sort_order' => 1,
            'is_primary' => false,
        ]);
        $this->actingAs($this->adminUser());

        // Act
        $response = $this->patch(route('admin.products.images.update', [$product, $secondImage]), [
            'alt_text' => 'Side view',
            'sort_order' => 3,
            'is_primary' => true,
        ]);

        // Assert
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertDatabaseHas('product_images', [
            'id' => $secondImage->id,
            'alt_text' => 'Side view',
            'sort_order' => 3,
            'is_primary' => true,
        ]);
        $this->assertFalse($firstImage->fresh()->is_primary);
    }

    public function test_deleting_primary_image_removes_file_and_promotes_another_image(): void
    {
        // Arrange
        Storage::fake('public');
        $product = $this->createProduct();
        $primaryImage = $product->images()->create([
            'path' => 'products/'.$product->id.'/primary.png',
            'alt_text' => 'Primary',
            'sort_order' => 0,
            'is_primary' => true,
        ]);
        $remainingImage = $product->images()->create([
            'path' => 'products/'.$product->id.'/secondary.png',
            'alt_text' => 'Secondary',
            'sort_order' => 1,
            'is_primary' => false,
        ]);
        Storage::disk('public')->put($primaryImage->path, 'primary file');
        Storage::disk('public')->put($remainingImage->path, 'secondary file');
        $this->actingAs($this->adminUser());

        // Act
        $response = $this->delete(route('admin.products.images.destroy', [$product, $primaryImage]));

        // Assert
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertDatabaseMissing('product_images', ['id' => $primaryImage->id]);
        $this->assertTrue($remainingImage->fresh()->is_primary);
        Storage::disk('public')->assertMissing($primaryImage->path);
        Storage::disk('public')->assertExists($remainingImage->path);
    }

    public function test_admin_cannot_manage_another_products_image(): void
    {
        // Arrange
        Storage::fake('public');
        $product = $this->createProduct();
        $otherProduct = $this->createProduct('Other product', 'other-product');
        $image = $product->images()->create([
            'path' => 'products/'.$product->id.'/private.png',
            'sort_order' => 0,
            'is_primary' => true,
        ]);
        Storage::disk('public')->put($image->path, 'image file');
        $this->actingAs($this->adminUser());

        // Act / Assert
        $this->delete(route('admin.products.images.destroy', [$otherProduct, $image]))
            ->assertNotFound();

        $this->assertDatabaseHas('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_regular_user_cannot_upload_product_image(): void
    {
        // Arrange
        Storage::fake('public');
        $product = $this->createProduct();
        $this->actingAs(User::factory()->create());

        // Act / Assert
        $this->post(route('admin.products.images.store', $product), [
            'image' => UploadedFile::fake()->image('front.png'),
        ])->assertForbidden();

        $this->assertDatabaseCount('product_images', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function createProduct(string $name = 'Image Product', string $slug = 'image-product'): Product
    {
        $category = Category::create([
            'name' => 'Images',
            'slug' => 'images-'.str()->slug($slug),
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'price' => '10.00',
            'stock_quantity' => 2,
            'status' => 'active',
        ]);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }
}
