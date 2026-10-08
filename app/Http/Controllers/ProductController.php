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

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('status', 'active')
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->with(['category', 'images' => fn ($query) => $query->orderBy('sort_order')])
            ->when($categorySlug, function ($query, string $slug): void {
                $query->whereHas('category', fn ($categoryQuery) => $categoryQuery
                    ->where('slug', $slug)
                    ->where('is_active', true));
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('products.index', compact('categories', 'products'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'active' && $product->category()->where('is_active', true)->exists(), 404);

        $product->load([
            'category',
            'images' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return view('products.show', compact('product'));
    }
}
