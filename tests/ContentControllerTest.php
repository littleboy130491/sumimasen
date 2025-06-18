<?php

namespace Littleboy130491\Sumimasen\Tests;

use Littleboy130491\Sumimasen\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Http\Controllers\ContentController;
use Littleboy130491\Sumimasen\Models\Page;
use Littleboy130491\Sumimasen\Models\Post;
use Littleboy130491\Sumimasen\Tests\TestCase;

class ContentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->author = User::factory()->create();

        config([
            'cms.default_language' => 'en',
            'cms.language_available' => ['en' => 'English', 'id' => 'Indonesian'],
            'cms.content_models' => [
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
            ],
            'cms.static_page_model' => Page::class,
            'cms.static_page_slug' => 'pages',
            'cms.front_page_slug' => 'home',
            'cms.fallback_content_type' => 'posts',
        ]);

        Route::prefix('{lang}')
            ->whereIn('lang', ['en', 'id'])
            ->middleware(['setLocale'])
            ->group(function () {
                Route::get('/', [ContentController::class, 'home'])->name('cms.home');
                Route::get('/posts/{content_slug}', [ContentController::class, 'singleContent'])
                    ->defaults('content_type_key', 'posts')
                    ->name('cms.single.content');
                Route::get('/posts', [ContentController::class, 'archiveContent'])
                    ->defaults('content_type_archive_key', 'posts')
                    ->name('cms.archive.content');
                Route::get('/{page_slug}', [ContentController::class, 'staticPage'])->name('cms.static.page');
            });
    }

    /** @test */
    public function it_loads_a_standard_page_successfully()
    {
        $this->createTestTemplate('templates.default', 'Test page: {{ $content->title }} - {{ $content->content }}');

        Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'About Us'],
            'slug' => ['en' => 'about'],
            'content' => ['en' => 'This is the about page.'],
        ]);

        $this->get('/en/about')
            ->assertOk()
            ->assertSee('This is the about page.');
    }

    /** @test */
    public function it_loads_a_translated_page_with_its_specific_slug()
    {
        // Create a view file for testing
        $this->createTestTemplate('templates.default', 'Test page: {{ $content->title }}');

        Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['id' => 'Tentang Kami'],
            'slug' => ['id' => 'tentang'],
        ]);

        $this->get('/id/tentang')
            ->assertOk()
            ->assertSee('Tentang Kami');
    }

    /** @test */
    public function it_loads_a_page_with_a_null_slug_via_default_language_fallback()
    {
        $this->createTestTemplate('templates.default', 'Test page: {{ $content->title }} - {{ $content->content }}');

        Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['id' => 'Hubungi Kami'],
            'slug' => ['en' => 'contact', 'id' => null],
            'content' => ['id' => 'Konten Indonesia.'],
        ]);

        $this->get('/id/contact')
            ->assertOk()
            ->assertSee('Hubungi Kami')
            ->assertSee('Konten Indonesia.');
    }

    /** @test */
    public function it_falls_back_to_default_language_content_when_translated_content_is_null()
    {
        $this->createTestTemplate('templates.default', 'Test page: {{ $content->title }} - {{ $content->content }}');

        Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['id' => 'Layanan'],
            'slug' => ['id' => 'layanan'],
            'content' => ['en' => 'This is our service content.', 'id' => null],
        ]);

        $this->get('/id/layanan')
            ->assertOk()
            ->assertSee('Layanan')
            ->assertSee('This is our service content.');
    }

    /** @test */
    public function it_loads_a_single_post()
    {
        $this->createTestTemplate('templates.default', 'Single post: {{ $content->title }} - {{ $content->content ?? "No content" }}');

        Post::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'My Blog Post'],
            'slug' => ['en' => 'my-blog-post'],
            'content' => ['en' => 'This is my blog post content.'],
        ]);

        $this->get('/en/posts/my-blog-post')
            ->assertOk()
            ->assertSee('My Blog Post')
            ->assertSee('This is my blog post content.');
    }

    /** @test */
    public function it_loads_the_home_page_with_the_correct_slug()
    {
        $this->createTestTemplate('templates.default', 'Test page: {{ $content->title }} - {{ $content->content }}');

        Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'Home'],
            'slug' => ['en' => 'home'],
            'content' => ['en' => 'Welcome to the home page.'],
        ]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('Welcome to the home page.');
    }

    /** @test */
    public function it_falls_back_to_the_first_published_page_if_home_slug_is_missing()
    {
        $this->createTestTemplate('templates.default', 'Test page: {{ $content->title }} - {{ $content->content }}');

        Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'First Page'],
            'content' => ['en' => 'This is the first ever page.'],
            'slug' => ['en' => 'not-home'],
        ]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('This is the first ever page.');
    }

    /** @test */
    public function it_loads_an_archive_page()
    {
        $this->createTestTemplate('templates.default', 'Archive: {{ $title }} Posts: @foreach($posts as $post) {{ $post->title }} @endforeach');

        Post::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'My First Post'],
            'slug' => ['en' => 'my-first-post'],
        ]);
        Post::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'My Second Post'],
            'slug' => ['en' => 'my-second-post'],
        ]);

        $this->get('/en/posts')
            ->assertOk()
            ->assertSee('My First Post')
            ->assertSee('My Second Post');
    }

    /** @test */
    public function it_returns_a_404_for_a_non_existent_page()
    {
        $this->get('/en/this-page-does-not-exist')
            ->assertNotFound();
    }

    // Tests for findContent private method

    /** @test */
    public function find_content_returns_content_for_requested_locale_when_available()
    {
        $page = Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'English Title', 'id' => 'Indonesian Title'],
            'slug' => ['en' => 'english-slug', 'id' => 'indonesian-slug'],
        ]);

        $controller = new ContentController;
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('findContent');
        $method->setAccessible(true);

        $result = $method->invoke($controller, Page::class, 'en', 'english-slug');

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals($page->id, $result->id);
    }

    /** @test */
    public function find_content_falls_back_to_default_locale_when_requested_locale_slug_is_null()
    {
        $page = Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'English Title', 'id' => 'Indonesian Title'],
            'slug' => ['en' => 'fallback-slug', 'id' => null],
        ]);

        $controller = new ContentController;
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('findContent');
        $method->setAccessible(true);

        $result = $method->invoke($controller, Page::class, 'id', 'fallback-slug');

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals($page->id, $result->id);
    }

    /** @test */
    public function find_content_returns_null_when_content_not_found_in_requested_locale()
    {
        $controller = new ContentController;
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('findContent');
        $method->setAccessible(true);

        $result = $method->invoke($controller, Page::class, 'en', 'non-existent-slug');

        $this->assertNull($result);
    }

    /** @test */
    public function find_content_returns_null_when_content_not_found_in_fallback_locale()
    {
        $controller = new ContentController;
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('findContent');
        $method->setAccessible(true);

        $result = $method->invoke($controller, Page::class, 'id', 'non-existent-slug');

        $this->assertNull($result);
    }

    /** @test */
    public function find_content_returns_null_for_unpublished_content()
    {
        Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Draft,
            'title' => ['en' => 'Draft Title'],
            'slug' => ['en' => 'draft-slug'],
        ]);

        $controller = new ContentController;
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('findContent');
        $method->setAccessible(true);

        $result = $method->invoke($controller, Page::class, 'en', 'draft-slug');

        $this->assertNull($result);
    }

    /** @test */
    public function find_content_works_with_default_locale_when_requested_locale_equals_default()
    {
        $page = Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'English Title'],
            'slug' => ['en' => 'english-slug'],
        ]);

        $controller = new ContentController;
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('findContent');
        $method->setAccessible(true);

        $result = $method->invoke($controller, Page::class, 'en', 'english-slug');

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals($page->id, $result->id);
    }

    /** @test */
    public function find_content_falls_back_to_default_locale_when_slug_missing_in_requested_locale()
    {
        // Create content with only default locale slug
        $page = Page::create([
            'author_id' => $this->author->id,
            'status' => ContentStatus::Published,
            'title' => ['en' => 'English Title', 'id' => 'Indonesian Title'],
            'slug' => ['en' => 'only-english-slug'],
        ]);

        $controller = new ContentController;
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('findContent');
        $method->setAccessible(true);

        $result = $method->invoke($controller, Page::class, 'id', 'only-english-slug');

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals($page->id, $result->id);
    }

    /**
     * Helper method to create test templates in a test-specific directory
     */
    protected function createTestTemplate(string $template, string $content): void
    {
        // Use test-specific directory to avoid conflicts
        $templatePath = resource_path('views/test-templates/'.str_replace('.', '/', str_replace('templates.', '', $template)).'.blade.php');

        // Create directory if it doesn't exist
        $directory = dirname($templatePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($templatePath, $content);

        // Update view paths temporarily for tests
        view()->addLocation(resource_path('views/test-templates'));

        // Override the template resolution for tests
        app()->bind('test.template', function () use ($content) {
            return $content;
        });
    }

    protected function tearDown(): void
    {
        // Clean up test-specific template files only
        $testTemplatePath = resource_path('views/test-templates');
        if (is_dir($testTemplatePath)) {
            $this->removeDirectory($testTemplatePath);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $path = $directory.'/'.$file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}
