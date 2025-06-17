<?php

namespace Littleboy130491\Sumimasen\Tests\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Models\Category;
use Littleboy130491\Sumimasen\Models\Post;
use Littleboy130491\Sumimasen\Tests\TestCase;

class CategoryModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_category()
    {
        $category = Category::create([
            'title' => ['en' => 'Test Category'],
            'slug' => ['en' => 'test-category'],
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    /** @test */
    public function it_has_translatable_attributes()
    {
        $category = Category::create([
            'title' => ['en' => 'English Category', 'id' => 'Indonesian Category'],
            'content' => ['en' => 'English content', 'id' => 'Indonesian content'],
            'slug' => ['en' => 'english-category', 'id' => 'indonesian-category'],
        ]);

        $this->assertEquals('English Category', $category->getTranslation('title', 'en'));
        $this->assertEquals('Indonesian Category', $category->getTranslation('title', 'id'));
        $this->assertEquals('English content', $category->getTranslation('content', 'en'));
        $this->assertEquals('Indonesian content', $category->getTranslation('content', 'id'));
    }

    /** @test */
    public function it_can_have_a_parent_category()
    {
        $parentCategory = Category::create([
            'title' => ['en' => 'Parent Category'],
            'slug' => ['en' => 'parent-category'],
        ]);

        $childCategory = Category::create([
            'title' => ['en' => 'Child Category'],
            'slug' => ['en' => 'child-category'],
            'parent_id' => $parentCategory->id,
        ]);

        $this->assertInstanceOf(Category::class, $childCategory->parent);
        $this->assertEquals($parentCategory->id, $childCategory->parent->id);
    }

    /** @test */
    public function it_can_have_children_categories()
    {
        $parentCategory = Category::create([
            'title' => ['en' => 'Parent Category'],
            'slug' => ['en' => 'parent-category'],
        ]);

        $child1 = Category::create([
            'title' => ['en' => 'Child 1'],
            'slug' => ['en' => 'child-1'],
            'parent_id' => $parentCategory->id,
        ]);

        $child2 = Category::create([
            'title' => ['en' => 'Child 2'],
            'slug' => ['en' => 'child-2'],
            'parent_id' => $parentCategory->id,
        ]);

        $this->assertCount(2, $parentCategory->children);
        $this->assertTrue($parentCategory->children->contains($child1));
        $this->assertTrue($parentCategory->children->contains($child2));
    }

    /** @test */
    public function it_can_have_posts()
    {
        $category = Category::create([
            'title' => ['en' => 'Test Category'],
            'slug' => ['en' => 'test-category'],
        ]);

        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        $category->posts()->attach([$post1->id, $post2->id]);

        $this->assertCount(2, $category->posts);
        $this->assertTrue($category->posts->contains($post1));
        $this->assertTrue($category->posts->contains($post2));
    }

    /** @test */
    public function it_can_have_a_featured_image()
    {
        $media = Media::factory()->create();

        $category = Category::create([
            'title' => ['en' => 'Test Category'],
            'slug' => ['en' => 'test-category'],
            'featured_image' => $media->id,
        ]);

        $this->assertInstanceOf(Media::class, $category->featuredImage);
        $this->assertEquals($media->id, $category->featuredImage->id);
    }

    /** @test */
    public function it_casts_menu_order_to_integer()
    {
        $category = Category::create([
            'title' => ['en' => 'Test Category'],
            'slug' => ['en' => 'test-category'],
            'menu_order' => '5',
        ]);

        $this->assertIsInt($category->menu_order);
        $this->assertEquals(5, $category->menu_order);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $category = Category::create([
            'title' => ['en' => 'Test Category'],
            'slug' => ['en' => 'test-category'],
        ]);

        $category->delete();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertNotNull($category->fresh()->deleted_at);
    }
}
