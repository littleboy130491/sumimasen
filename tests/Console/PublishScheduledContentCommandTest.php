<?php

namespace Littleboy130491\Sumimasen\Tests\Console;

use Littleboy130491\Sumimasen\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Models\Page;
use Littleboy130491\Sumimasen\Models\Post;
use Littleboy130491\Sumimasen\Tests\TestCase;

class PublishScheduledContentCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->author = User::factory()->create();

        // Configure CMS settings for testing
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

    /** @test */
    public function it_publishes_scheduled_content_when_time_has_passed()
    {
        // Create scheduled content with publish time in the past
        $pastTime = Carbon::now()->subHour();

        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Scheduled Page'],
            'slug' => ['en' => 'scheduled-page'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $pastTime,
        ]);

        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Scheduled Post'],
            'slug' => ['en' => 'scheduled-post'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $pastTime,
        ]);

        $this->artisan('cms:publish-scheduled')
            ->expectsOutput('Published scheduled content for model: '.Page::class)
            ->expectsOutput('Published scheduled content for model: '.Post::class)
            ->expectsOutput('Scheduled content publishing complete.')
            ->assertSuccessful();

        // Assert content status was updated
        $this->assertEquals(ContentStatus::Published, $page->fresh()->status);
        $this->assertEquals(ContentStatus::Published, $post->fresh()->status);
    }

    /** @test */
    public function it_does_not_publish_scheduled_content_when_time_has_not_passed()
    {
        // Create scheduled content with publish time in the future
        $futureTime = Carbon::now()->addHour();

        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Future Scheduled Page'],
            'slug' => ['en' => 'future-scheduled-page'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $futureTime,
        ]);

        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Future Scheduled Post'],
            'slug' => ['en' => 'future-scheduled-post'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $futureTime,
        ]);

        $this->artisan('cms:publish-scheduled')
            ->assertSuccessful();

        // Assert content status remains scheduled
        $this->assertEquals(ContentStatus::Scheduled, $page->fresh()->status);
        $this->assertEquals(ContentStatus::Scheduled, $post->fresh()->status);
    }

    /** @test */
    public function it_does_not_affect_already_published_content()
    {
        $publishedPage = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Already Published Page'],
            'slug' => ['en' => 'published-page'],
            'status' => ContentStatus::Published,
            'published_at' => Carbon::now()->subDay(),
        ]);

        $this->artisan('cms:publish-scheduled')
            ->assertSuccessful();

        // Status should remain unchanged
        $this->assertEquals(ContentStatus::Published, $publishedPage->fresh()->status);
    }

    /** @test */
    public function it_does_not_affect_draft_content()
    {
        $draftPage = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Draft Page'],
            'slug' => ['en' => 'draft-page'],
            'status' => ContentStatus::Draft,
            'published_at' => Carbon::now()->subHour(),
        ]);

        $this->artisan('cms:publish-scheduled')
            ->assertSuccessful();

        // Status should remain unchanged
        $this->assertEquals(ContentStatus::Draft, $draftPage->fresh()->status);
    }

    /** @test */
    public function it_publishes_content_at_exact_time()
    {
        // Create content scheduled for exactly now
        $now = Carbon::now();

        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Exact Time Page'],
            'slug' => ['en' => 'exact-time-page'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $now,
        ]);

        $this->artisan('cms:publish-scheduled')
            ->assertSuccessful();

        $this->assertEquals(ContentStatus::Published, $page->fresh()->status);
    }

    /** @test */
    public function it_processes_multiple_scheduled_items()
    {
        $pastTime = Carbon::now()->subHour();

        // Create multiple scheduled items
        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $items[] = Page::create([
                'author_id' => $this->author->id,
                'title' => ['en' => "Scheduled Page {$i}"],
                'slug' => ['en' => "scheduled-page-{$i}"],
                'status' => ContentStatus::Scheduled,
                'published_at' => $pastTime,
            ]);
        }

        $this->artisan('cms:publish-scheduled')
            ->assertSuccessful();

        // All items should be published
        foreach ($items as $item) {
            $this->assertEquals(ContentStatus::Published, $item->fresh()->status);
        }
    }

    /** @test */
    public function it_handles_mixed_scheduled_times()
    {
        $pastTime = Carbon::now()->subHour();
        $futureTime = Carbon::now()->addHour();

        $shouldPublish = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Should Publish'],
            'slug' => ['en' => 'should-publish'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $pastTime,
        ]);

        $shouldNotPublish = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Should Not Publish'],
            'slug' => ['en' => 'should-not-publish'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $futureTime,
        ]);

        $this->artisan('cms:publish-scheduled')
            ->assertSuccessful();

        $this->assertEquals(ContentStatus::Published, $shouldPublish->fresh()->status);
        $this->assertEquals(ContentStatus::Scheduled, $shouldNotPublish->fresh()->status);
    }

    /** @test */
    public function it_processes_all_configured_content_models()
    {
        $pastTime = Carbon::now()->subHour();

        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Scheduled Page'],
            'slug' => ['en' => 'scheduled-page'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $pastTime,
        ]);

        $post = Post::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Scheduled Post'],
            'slug' => ['en' => 'scheduled-post'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $pastTime,
        ]);

        $this->artisan('cms:publish-scheduled')
            ->expectsOutput('Published scheduled content for model: '.Page::class)
            ->expectsOutput('Published scheduled content for model: '.Post::class)
            ->assertSuccessful();

        $this->assertEquals(ContentStatus::Published, $page->fresh()->status);
        $this->assertEquals(ContentStatus::Published, $post->fresh()->status);
    }

    /** @test */
    public function it_handles_empty_scheduled_content_gracefully()
    {
        // No scheduled content exists
        $this->artisan('cms:publish-scheduled')
            ->expectsOutput('Scheduled content publishing complete.')
            ->assertSuccessful();
    }

    /** @test */
    public function it_skips_non_content_models()
    {
        // Add a non-content model to config
        Config::set('cms.content_models.categories', [
            'model' => \Littleboy130491\Sumimasen\Models\Category::class,
            'type' => 'taxonomy', // Not 'content'
        ]);

        $pastTime = Carbon::now()->subHour();

        $page = Page::create([
            'author_id' => $this->author->id,
            'title' => ['en' => 'Scheduled Page'],
            'slug' => ['en' => 'scheduled-page'],
            'status' => ContentStatus::Scheduled,
            'published_at' => $pastTime,
        ]);

        $this->artisan('cms:publish-scheduled')
            ->expectsOutput('Published scheduled content for model: '.Page::class)
            ->doesntExpectOutput('Published scheduled content for model: '.\Littleboy130491\Sumimasen\Models\Category::class)
            ->assertSuccessful();

        $this->assertEquals(ContentStatus::Published, $page->fresh()->status);
    }

    /** @test */
    public function it_handles_models_without_required_columns()
    {
        // Add a model without status/published_at columns to config
        Config::set('cms.content_models.users', [
            'model' => User::class,
            'type' => 'content',
        ]);

        $this->artisan('cms:publish-scheduled')
            ->expectsOutputToContain('Skipping model')
            ->expectsOutputToContain('missing required columns')
            ->assertSuccessful();
    }
}
