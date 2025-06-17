<?php

namespace Littleboy130491\Sumimasen\Tests\Models;

use App\Models\User;
use Awcodes\Curator\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Models\Category;
use Littleboy130491\Sumimasen\Models\Comment;
use Littleboy130491\Sumimasen\Models\Post;
use Littleboy130491\Sumimasen\Models\Tag;
use Littleboy130491\Sumimasen\Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->author = User::factory()->create();
    }

    /** @test */
    public function it_can_create_a_post()
    {
        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'content' => ['en' => 'Test content'],
            'slug' => ['en' => 'test-post'],
            'status' => ContentStatus::Published,
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'author_id' => $this->author->id,
        ]);
    }

    /** @test */
    public function it_has_translatable_attributes()
    {
        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'English Title', 'id' => 'Indonesian Title'],
            'content' => ['en' => 'English content', 'id' => 'Indonesian content'],
            'slug' => ['en' => 'english-slug', 'id' => 'indonesian-slug'],
            'status' => ContentStatus::Published,
        ]);

        $this->assertEquals('English Title', $post->getTranslation('title', 'en'));
        $this->assertEquals('Indonesian Title', $post->getTranslation('title', 'id'));
        $this->assertEquals('English content', $post->getTranslation('content', 'en'));
        $this->assertEquals('Indonesian content', $post->getTranslation('content', 'id'));
    }

    /** @test */
    public function it_belongs_to_an_author()
    {
        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'status' => ContentStatus::Published,
        ]);

        $this->assertInstanceOf(User::class, $post->author);
        $this->assertEquals($this->author->id, $post->author->id);
    }

    /** @test */
    public function it_can_have_categories()
    {
        $category1 = Category::create([
            'title' => ['en' => 'Category 1'],
            'slug' => ['en' => 'category-1'],
        ]);

        $category2 = Category::create([
            'title' => ['en' => 'Category 2'],
            'slug' => ['en' => 'category-2'],
        ]);

        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'status' => ContentStatus::Published,
        ]);

        $post->categories()->attach([$category1->id, $category2->id]);

        $this->assertCount(2, $post->categories);
        $this->assertTrue($post->categories->contains($category1));
        $this->assertTrue($post->categories->contains($category2));
    }

    /** @test */
    public function it_can_have_tags()
    {
        $tag1 = Tag::create([
            'title' => ['en' => 'Tag 1'],
            'slug' => ['en' => 'tag-1'],
        ]);

        $tag2 = Tag::create([
            'title' => ['en' => 'Tag 2'],
            'slug' => ['en' => 'tag-2'],
        ]);

        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'status' => ContentStatus::Published,
        ]);

        $post->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertCount(2, $post->tags);
        $this->assertTrue($post->tags->contains($tag1));
        $this->assertTrue($post->tags->contains($tag2));
    }

    /** @test */
    public function it_can_have_comments()
    {
        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'status' => ContentStatus::Published,
        ]);

        $comment = Comment::create([
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'Great post!',
        ]);

        $this->assertCount(1, $post->comments);
        $this->assertEquals($comment->id, $post->comments->first()->id);
    }

    /** @test */
    public function it_can_have_a_featured_image()
    {
        $media = Media::factory()->create();

        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'featured_image' => $media->id,
            'status' => ContentStatus::Published,
        ]);

        $this->assertInstanceOf(Media::class, $post->featuredImage);
        $this->assertEquals($media->id, $post->featuredImage->id);
    }

    /** @test */
    public function it_casts_featured_to_boolean()
    {
        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'featured' => true,
            'status' => ContentStatus::Published,
        ]);

        $this->assertIsBool($post->featured);
        $this->assertTrue($post->featured);
    }

    /** @test */
    public function it_casts_status_to_enum()
    {
        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'status' => ContentStatus::Draft,
        ]);

        $this->assertInstanceOf(ContentStatus::class, $post->status);
        $this->assertEquals(ContentStatus::Draft, $post->status);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'status' => ContentStatus::Published,
        ]);

        $post->delete();

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $this->assertNotNull($post->fresh()->deleted_at);
    }

    /** @test */
    public function it_has_page_likes_trait()
    {
        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'status' => ContentStatus::Published,
        ]);

        $this->assertTrue(method_exists($post, 'likes'));
    }

    /** @test */
    public function it_has_page_views_trait()
    {
        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Post'],
            'status' => ContentStatus::Published,
        ]);

        $this->assertTrue(method_exists($post, 'views'));
    }
}
