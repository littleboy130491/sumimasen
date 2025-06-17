<?php

namespace Littleboy130491\Sumimasen\Tests\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Models\Page;
use Littleboy130491\Sumimasen\Models\Post;
use Littleboy130491\Sumimasen\Tests\TestCase;

class GenerateSitemapCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->author = User::factory()->create();

        // Configure CMS settings for testing
        Config::set('cms.language_available', [
            'en' => 'English',
            'id' => 'Indonesian',
        ]);

        Config::set('cms.content_models', [
            'pages' => [
                'model' => Page::class,
                'type' => 'content',
                'has_archive' => false,
                'has_single' => true,
            ],
            'posts' => [
                'model' => Post::class,
                'type' => 'content',
                'has_archive' => true,
                'has_single' => true,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up generated sitemap file
        $sitemapPath = public_path('sitemap.xml');
        if (File::exists($sitemapPath)) {
            File::delete($sitemapPath);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_generates_sitemap_successfully()
    {
        // Create test content
        Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Home Page', 'id' => 'Halaman Utama'],
            'slug' => ['en' => 'home', 'id' => 'beranda'],
            'status' => ContentStatus::Published,
        ]);

        Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Blog Post', 'id' => 'Artikel Blog'],
            'slug' => ['en' => 'blog-post', 'id' => 'artikel-blog'],
            'status' => ContentStatus::Published,
        ]);

        $this->artisan('sitemap:generate')
            ->expectsOutput('Sitemap generated successfully.')
            ->assertSuccessful();

        $this->assertFileExists(public_path('sitemap.xml'));
    }

    /** @test */
    public function it_includes_published_content_only()
    {
        // Create published content
        Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Published Page'],
            'slug' => ['en' => 'published-page'],
            'status' => ContentStatus::Published,
        ]);

        // Create draft content (should not be included)
        Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Draft Page'],
            'slug' => ['en' => 'draft-page'],
            'status' => ContentStatus::Draft,
        ]);

        $this->artisan('sitemap:generate')
            ->expectsOutputToContain('Adding: http://localhost/en/published-page')
            ->doesntExpectOutputToContain('Adding: http://localhost/en/draft-page')
            ->assertSuccessful();
    }

    /** @test */
    public function it_includes_all_configured_locales()
    {
        Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Multi-lang Page', 'id' => 'Halaman Multi-bahasa'],
            'slug' => ['en' => 'multi-lang', 'id' => 'multi-bahasa'],
            'status' => ContentStatus::Published,
        ]);

        $this->artisan('sitemap:generate')
            ->expectsOutputToContain('Adding: http://localhost/en/multi-lang')
            ->expectsOutputToContain('Adding: http://localhost/id/multi-bahasa')
            ->assertSuccessful();
    }

    /** @test */
    public function it_handles_missing_translations_gracefully()
    {
        // Create content with missing Indonesian translation
        Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'English Only Page'],
            'slug' => ['en' => 'english-only'],
            'status' => ContentStatus::Published,
        ]);

        $this->artisan('sitemap:generate')
            ->expectsOutputToContain('Adding: http://localhost/en/english-only')
            ->expectsOutputToContain('Missing slug for id on record ID')
            ->assertSuccessful();
    }

    /** @test */
    public function it_uses_correct_route_prefix_for_different_content_types()
    {
        // Create a page (no prefix)
        Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'About Page'],
            'slug' => ['en' => 'about'],
            'status' => ContentStatus::Published,
        ]);

        // Create a post (with 'posts/' prefix)
        Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Blog Post'],
            'slug' => ['en' => 'blog-post'],
            'status' => ContentStatus::Published,
        ]);

        $this->artisan('sitemap:generate')
            ->expectsOutputToContain('Adding: http://localhost/en/about')
            ->expectsOutputToContain('Adding: http://localhost/en/posts/blog-post')
            ->assertSuccessful();
    }

    /** @test */
    public function it_creates_sitemap_file_in_public_directory()
    {
        Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Test Page'],
            'slug' => ['en' => 'test-page'],
            'status' => ContentStatus::Published,
        ]);

        $sitemapPath = public_path('sitemap.xml');

        // Ensure file doesn't exist before command
        $this->assertFileDoesNotExist($sitemapPath);

        $this->artisan('sitemap:generate')
            ->assertSuccessful();

        // Verify file was created
        $this->assertFileExists($sitemapPath);

        // Verify file contains XML content
        $content = File::get($sitemapPath);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('http://localhost/en/test-page', $content);
    }

    /** @test */
    public function it_handles_empty_content_gracefully()
    {
        // Run command with no content
        $this->artisan('sitemap:generate')
            ->expectsOutput('Sitemap generated successfully.')
            ->assertSuccessful();

        // Sitemap should still be created even if empty
        $this->assertFileExists(public_path('sitemap.xml'));
    }

    /** @test */
    public function it_processes_multiple_content_types()
    {
        // Create content of different types
        Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Home Page'],
            'slug' => ['en' => 'home'],
            'status' => ContentStatus::Published,
        ]);

        Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'First Post'],
            'slug' => ['en' => 'first-post'],
            'status' => ContentStatus::Published,
        ]);

        Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Second Post'],
            'slug' => ['en' => 'second-post'],
            'status' => ContentStatus::Published,
        ]);

        $this->artisan('sitemap:generate')
            ->expectsOutputToContain('Adding: http://localhost/en/home')
            ->expectsOutputToContain('Adding: http://localhost/en/posts/first-post')
            ->expectsOutputToContain('Adding: http://localhost/en/posts/second-post')
            ->assertSuccessful();
    }

    /** @test */
    public function it_overwrites_existing_sitemap_file()
    {
        // Create initial sitemap
        File::put(public_path('sitemap.xml'), '<xml>Old content</xml>');

        Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'New Page'],
            'slug' => ['en' => 'new-page'],
            'status' => ContentStatus::Published,
        ]);

        $this->artisan('sitemap:generate')
            ->assertSuccessful();

        $content = File::get(public_path('sitemap.xml'));
        $this->assertStringNotContainsString('Old content', $content);
        $this->assertStringContainsString('http://localhost/en/new-page', $content);
    }
}
