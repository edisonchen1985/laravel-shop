<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_returns_its_parent(): void
    {
        // Arrange
        $parent = Category::create([
            'name' => 'Clothing',
            'slug' => 'clothing',
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Shirts',
            'slug' => 'shirts',
        ]);

        // Act
        $childParent = $child->parent;

        // Assert
        $this->assertTrue($childParent->is($parent));
    }

    public function test_category_returns_its_children(): void
    {
        // Arrange
        $parent = Category::create([
            'name' => 'Clothing',
            'slug' => 'clothing',
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Shirts',
            'slug' => 'shirts',
        ]);

        // Act
        $children = $parent->children;

        // Assert
        $this->assertCount(1, $children);
        $this->assertTrue($children->contains(fn (Category $category): bool => $category->is($child)));
    }
}
