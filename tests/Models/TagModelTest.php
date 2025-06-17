<?php

namespace Littleboy130491\Sumimasen\Tests\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Models\Post;
use Littleboy130491\Sumimasen\Models\Tag;
use Littleboy130491\Sumimasen\Tests\TestCase;

class TagModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_tag()
    {
        $tag = Tag::create([
            'title' => ['en' => 'Test Tag'],
            'slug' => ['en' => 'test-tag'],
        ]);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
        ]);
    }

    /** @test */
    public function it_has_translatable_attributes()
    {
        $tag = Tag::create([
            'title' => ['en' => 'English Tag', 'id' => 'Indonesian Tag'],
            'content' => ['en' => 'English content', 'id' => 'Indonesian content'],
            'slug' => ['en' => 'english-tag', 'id' => 'indonesian-tag'],
        ]);

        $this->assertEquals('English Tag', $tag->getTranslation('title', 'en'));
        $this->assertEquals('Indonesian Tag', $tag->getTranslation('title', 'id'));
        $this->assertEquals('English content', $tag->getTranslation('content', 'en'));
        $this->assertEquals('Indonesian content', $tag->getTranslation('content', 'id'));
    }

    /** @test */
    public function it_can_have_posts()
    {
        $tag = Tag::create([
            'title' => ['en' => 'Test Tag'],
            'slug' => ['en' => 'test-tag'],
        ]);

        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        $tag->posts()->attach([$post1->id, $post2->id]);

        $this->assertCount(2, $tag->posts);
        $this->assertTrue($tag->posts->contains($post1));
        $this->assertTrue($tag->posts->contains($post2));
    }

    /** @test */
    public function it_can_have_a_featured_image()
    {
        $media = Media::factory()->create();
        
        $tag = Tag::create([
            'title' => ['en' => 'Test Tag'],
            'slug' => ['en' => 'test-tag'],
            'featured_image' => $media->id,
        ]);

        $this->assertInstanceOf(Media::class, $tag->featuredImage);
        $this->assertEquals($media->id, $tag->featuredImage->id);
    }

    /** @test */
    public function it_casts_menu_order_to_integer()
    {
        $tag = Tag::create([
            'title' => ['en' => 'Test Tag'],
            'slug' => ['en' => 'test-tag'],
            'menu_order' => '3',
        ]);

        $this->assertIsInt($tag->menu_order);
        $this->assertEquals(3, $tag->menu_order);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $tag = Tag::create([
            'title' => ['en' => 'Test Tag'],
            'slug' => ['en' => 'test-tag'],
        ]);

        $tag->delete();

        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
        $this->assertNotNull($tag->fresh()->deleted_at);
    }
}