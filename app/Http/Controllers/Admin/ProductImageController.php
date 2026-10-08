<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Http\Requests\Admin\UpdateProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        $uploadedImage = $validated['image'];
        $path = $uploadedImage->store('products/'.$product->id, 'public');

        try {
            DB::transaction(function () use ($product, $validated, $path): void {
                $isPrimary = (bool) ($validated['is_primary'] ?? false)
                    || ! $product->images()->exists();

                if ($isPrimary) {
                    $product->images()->update(['is_primary' => false]);
                }

                $product->images()->create([
                    'path' => $path,
                    'alt_text' => $validated['alt_text'] ?? null,
                    'sort_order' => $validated['sort_order'] ?? 0,
                    'is_primary' => $isPrimary,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        return to_route('admin.products.edit', $product)
            ->with('status', 'Product image uploaded.');
    }

    public function update(
        UpdateProductImageRequest $request,
        Product $product,
        ProductImage $productImage,
    ): RedirectResponse {
        $productImage = $product->images()->findOrFail($productImage->id);
        $validated = $request->validated();

        DB::transaction(function () use ($product, $productImage, $validated): void {
            if ($validated['is_primary']) {
                $product->images()->whereKeyNot($productImage->id)->update(['is_primary' => false]);
            }

            $productImage->update($validated);
        });

        return to_route('admin.products.edit', $product)
            ->with('status', 'Product image updated.');
    }

    public function destroy(Product $product, ProductImage $productImage): RedirectResponse
    {
        $productImage = $product->images()->findOrFail($productImage->id);
        $wasPrimary = $productImage->is_primary;
        $path = $productImage->path;

        DB::transaction(function () use ($product, $productImage, $wasPrimary): void {
            $productImage->delete();

            if ($wasPrimary) {
                $nextImage = $product->images()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                $nextImage?->update(['is_primary' => true]);
            }
        });

        Storage::disk('public')->delete($path);

        return to_route('admin.products.edit', $product)
            ->with('status', 'Product image deleted.');
    }
}
