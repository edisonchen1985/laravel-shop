<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartMutationLockOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_item_locks_user_cart_cart_item_then_product(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = $this->createProduct();
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        // Act
        app(CartService::class)->addItem($user, $product->id, 1);

        // Assert
        $this->assertLockQueriesFollowOrder($queries, ['users', 'carts', 'cart_items', 'products']);
    }

    public function test_update_item_locks_user_cart_cart_item_then_product(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = $this->createProduct();
        $cartItem = $this->createCartItem($user, $product);
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        // Act
        app(CartService::class)->updateItem($user, $cartItem->id, 2);

        // Assert
        $this->assertLockQueriesFollowOrder($queries, ['users', 'carts', 'cart_items', 'products']);
    }

    public function test_delete_item_locks_user_cart_then_cart_item(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = $this->createProduct();
        $cartItem = $this->createCartItem($user, $product);
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        // Act
        app(CartService::class)->removeItem($user, $cartItem->id);

        // Assert
        $this->assertLockQueriesFollowOrder($queries, ['users', 'carts', 'cart_items']);
    }

    public function test_checkout_locks_user_cart_cart_items_then_products(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->createCartItem($user, $product);
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        // Act
        app(CheckoutService::class)->checkout($user);

        // Assert
        $this->assertLockQueriesFollowOrder($queries, ['users', 'carts', 'cart_items', 'products']);
    }

    /**
     * @param  array<int, string>  $queries
     * @param  array<int, string>  $tables
     */
    private function assertLockQueriesFollowOrder(array $queries, array $tables): void
    {
        $queryIndex = 0;
        $driver = DB::connection()->getDriverName();

        foreach ($tables as $table) {
            $matched = false;

            for ($index = $queryIndex; $index < count($queries); $index++) {
                if (! $this->queryReadsTable($queries[$index], $table)) {
                    continue;
                }

                if ($driver === 'mysql') {
                    $this->assertStringContainsString('for update', strtolower($queries[$index]));
                }

                $queryIndex = $index + 1;
                $matched = true;
                break;
            }

            $this->assertTrue($matched, "Expected a SELECT from [{$table}] in the lock acquisition order.");
        }
    }

    private function queryReadsTable(string $query, string $table): bool
    {
        return preg_match('/\bfrom\s+[`"]?'.preg_quote($table, '/').'[`"]?/i', $query) === 1;
    }

    private function createCartItem(User $user, Product $product): CartItem
    {
        $cart = Cart::create(['user_id' => $user->id]);

        return CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    private function createProduct(): Product
    {
        $number = Category::query()->count() + 1;
        $category = Category::create([
            'name' => 'Category '.$number,
            'slug' => 'category-'.$number,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Product '.$number,
            'slug' => 'product-'.$number,
            'price' => '10.00',
            'stock_quantity' => 10,
            'status' => 'active',
        ]);
    }
}
