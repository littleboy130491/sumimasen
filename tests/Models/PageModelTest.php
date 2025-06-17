<?php

namespace Littleboy130491\Sumimasen\Tests\Models;

use App\Models\User;
use Awcodes\Curator\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Models\Page;
use Littleboy130491\Sumimasen\Tests\TestCase;

class PageModelTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->author = User::factory()->create();
    }

    /** @test */
    public function it_can_create_a_page()
    {
        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'content' => ['en' => 'Test content'],
            'slug' => ['en' => 'test-page'],
            'status' => ContentStatus::Published,
        ]);

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'author_id' => $this->author->id,
        ]);
    }

    /** @test */
    public function it_has_translatable_attributes()
    {
        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'English Title', 'id' => 'Indonesian Title'],
            'content' => ['en' => 'English content', 'id' => 'Indonesian content'],
            'slug' => ['en' => 'english-slug', 'id' => 'indonesian-slug'],
            'status' => ContentStatus::Published,
        ]);

        $this->assertEquals('English Title', $page->getTranslation('title', 'en'));
        $this->assertEquals('Indonesian Title', $page->getTranslation('title', 'id'));
        $this->assertEquals('English content', $page->getTranslation('content', 'en'));
        $this->assertEquals('Indonesian content', $page->getTranslation('content', 'id'));
    }

    /** @test */
    public function it_casts_status_to_enum()
    {
        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'status' => ContentStatus::Published,
        ]);

        $this->assertInstanceOf(ContentStatus::class, $page->status);
        $this->assertEquals(ContentStatus::Published, $page->status);
    }

    /** @test */
    public function it_casts_custom_fields_to_array()
    {
        $customFields = ['key1' => 'value1', 'key2' => 'value2'];
        
        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'custom_fields' => $customFields,
            'status' => ContentStatus::Published,
        ]);

        $this->assertIsArray($page->custom_fields);
        $this->assertEquals($customFields, $page->custom_fields);
    }

    /** @test */
    public function it_belongs_to_an_author()
    {
        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'status' => ContentStatus::Published,
        ]);

        $this->assertInstanceOf(User::class, $page->author);
        $this->assertEquals($this->author->id, $page->author->id);
    }

    /** @test */
    public function it_can_have_a_parent_page()
    {
        $parentPage = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Parent Page'],
            'status' => ContentStatus::Published,
        ]);

        $childPage = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Child Page'],
            'parent_id' => $parentPage->id,
            'status' => ContentStatus::Published,
        ]);

        $this->assertInstanceOf(Page::class, $childPage->parent);
        $this->assertEquals($parentPage->id, $childPage->parent->id);
    }

    /** @test */
    public function it_can_have_a_featured_image()
    {
        $media = Media::factory()->create();
        
        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'featured_image' => $media->id,
            'status' => ContentStatus::Published,
        ]);

        $this->assertInstanceOf(Media::class, $page->featuredImage);
        $this->assertEquals($media->id, $page->featuredImage->id);
    }

    /** @test */
    public function it_processes_blocks_attribute_correctly()
    {
        $media = Media::factory()->create();
        
        $sections = [
            [
                'type' => 'text',
                'data' => [
                    'content' => 'Some text',
                    'media_id' => $media->id,
                ]
            ],
            [
                'type' => 'image',
                'data' => [
                    'caption' => 'Image caption',
                ]
            ]
        ];

        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'section' => $sections,
            'status' => ContentStatus::Published,
        ]);

        $blocks = $page->blocks;
        
        $this->assertIsArray($blocks);
        $this->assertCount(2, $blocks);
        $this->assertEquals('Some text', $blocks[0]['data']['content']);
        $this->assertEquals($media->url, $blocks[0]['data']['media_url']);
        $this->assertEquals('Image caption', $blocks[1]['data']['caption']);
        $this->assertArrayNotHasKey('media_url', $blocks[1]['data']);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'status' => ContentStatus::Published,
        ]);

        $page->delete();

        $this->assertSoftDeleted('pages', ['id' => $page->id]);
        $this->assertNotNull($page->fresh()->deleted_at);
    }

    /** @test */
    public function it_casts_published_at_to_datetime()
    {
        $publishedAt = now();
        
        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'published_at' => $publishedAt,
            'status' => ContentStatus::Published,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $page->published_at);
        $this->assertEquals($publishedAt->format('Y-m-d H:i:s'), $page->published_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_casts_menu_order_to_integer()
    {
        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'menu_order' => '5',
            'status' => ContentStatus::Published,
        ]);

        $this->assertIsInt($page->menu_order);
        $this->assertEquals(5, $page->menu_order);
    }
}