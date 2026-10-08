<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');
        $categorySlug = is_string($categorySlug) ? $categorySlug : null;
        $search = $request->query('search');
        $search = is_string($search) ? trim($search) : null;
        $search = $search !== '' ? $search : null;

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->with(['category', 'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->when($categorySlug, function ($query, string $slug): void {
                $query->whereHas('category', fn ($categoryQuery) => $categoryQuery
                    ->where('slug', $slug)
                    ->where('is_active', true));
            })
            ->when($search, fn ($query, string $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('products.index', compact('categories', 'products', 'categorySlug', 'search'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'active' && $product->category()->where('is_active', true)->exists(), 404);

        $product->load([
            'category',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        return view('products.show', compact('product'));
    }
}
