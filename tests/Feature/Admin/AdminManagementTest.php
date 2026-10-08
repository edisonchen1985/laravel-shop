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

    public function test_admin_product_prices_accept_zero_normal_and_decimal_maximum(): void
    {
        // Arrange
        $this->actingAs($this->adminUser());
        $category = $this->createCategory();

        // Act / Assert
        foreach ([
            ['0', '0.00'],
            ['19.90', '19.90'],
            ['99999999.99', '99999999.99'],
        ] as $index => [$price, $expectedPrice]) {
            $slug = 'price-boundary-'.$index;

            $this->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Price Boundary '.$index,
                'slug' => $slug,
                'sku' => null,
                'price' => $price,
                'stock_quantity' => 1,
                'status' => 'draft',
            ])->assertRedirect(route('admin.products.index'));

            $product = Product::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame($expectedPrice, $product->price);
        }
    }

    public function test_admin_product_price_rejects_amounts_outside_decimal_range(): void
    {
        // Arrange
        $this->actingAs($this->adminUser());
        $category = $this->createCategory();

        // Act / Assert
        foreach (['100000000', '19.999', '-0.01'] as $index => $price) {
            $slug = 'invalid-price-'.$index;

            $this->from(route('admin.products.create'))
                ->post(route('admin.products.store'), [
                    'category_id' => $category->id,
                    'name' => 'Invalid Price '.$index,
                    'slug' => $slug,
                    'sku' => null,
                    'price' => $price,
                    'stock_quantity' => 1,
                    'status' => 'draft',
                ])
                ->assertRedirect(route('admin.products.create'))
                ->assertSessionHasErrors('price');

            $this->assertDatabaseMissing('products', ['slug' => $slug]);
        }
    }

    public function test_admin_product_price_limit_also_applies_to_updates(): void
    {
        // Arrange
        $this->actingAs($this->adminUser());
        $category = $this->createCategory();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Existing Product',
            'slug' => 'existing-product',
            'price' => '19.90',
            'stock_quantity' => 2,
            'status' => 'draft',
        ]);
        $payload = [
            'category_id' => $category->id,
            'name' => 'Existing Product',
            'slug' => 'existing-product',
            'sku' => null,
            'stock_quantity' => 2,
            'status' => 'draft',
        ];

        // Act / Assert: the maximum value is accepted on update.
        $this->patch(route('admin.products.update', $product), array_merge($payload, [
            'price' => '99999999.99',
        ]))->assertRedirect(route('admin.products.index'));

        $this->assertSame('99999999.99', $product->refresh()->price);

        // Act / Assert: a value above the database limit is rejected without changing it.
        $this->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), array_merge($payload, [
                'price' => '100000000',
            ]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('price');

        $this->assertSame('99999999.99', $product->refresh()->price);
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
