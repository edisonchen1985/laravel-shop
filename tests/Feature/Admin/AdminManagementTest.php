<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_area(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_admin_area(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_soft_delete_product(): void
    {
        $admin = $this->adminUser();
        $category = $this->createCategory();

        $payload = [
            'category_id' => $category->id,
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'description' => 'A sturdy bag.',
            'sku' => 'BAG-001',
            'price' => '19.90',
            'stock_quantity' => 8,
            'status' => 'active',
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('slug', 'canvas-bag')->firstOrFail();
        $this->assertSame('19.90', $product->price);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), array_merge($payload, [
                'name' => 'Updated Canvas Bag',
                'slug' => 'updated-canvas-bag',
                'stock_quantity' => 4,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Canvas Bag',
            'stock_quantity' => 4,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_create_and_deactivate_category(): void
    {
        $admin = $this->adminUser();
        $payload = [
            'name' => 'Accessories',
            'slug' => 'accessories',
            'description' => 'Useful accessories.',
            'is_active' => '1',
        ];

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), $payload)
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('slug', 'accessories')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.categories.update', $category), array_merge($payload, [
                'is_active' => '0',
            ]))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function createCategory(): Category
    {
        return Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);
    }
}
