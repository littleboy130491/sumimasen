<?php

namespace Littleboy130491\Sumimasen\Tests\Livewire;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Livewire\LikeButton;
use Littleboy130491\Sumimasen\Models\Post;
use Littleboy130491\Sumimasen\Tests\TestCase;
use Livewire\Livewire;

class LikeButtonTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->author = User::factory()->create();
        $this->post = Post::factory()->create(['author_id' => $this->author->id]);
    }

    /** @test */
    public function it_can_render_like_button_component()
    {
        Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
        ])
            ->assertSuccessful()
            ->assertViewIs('livewire.like-button');
    }

    /** @test */
    public function it_initializes_with_correct_state()
    {
        Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
        ])
            ->assertSet('hasLiked', false)
            ->assertSet('likesCount', 0)
            ->assertSet('content.id', $this->post->id)
            ->assertSet('lang', 'en')
            ->assertSet('contentType', 'posts');
    }

    /** @test */
    public function it_can_toggle_like_from_unliked_to_liked()
    {
        Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
        ])
            ->call('toggleLike')
            ->assertSet('hasLiked', true)
            ->assertSet('likesCount', 1)
            ->assertDispatched('like-toggled', [
                'contentId' => $this->post->id,
                'liked' => true,
                'likesCount' => 1,
            ]);

        $this->assertEquals(1, $this->post->fresh()->page_likes);
    }

    /** @test */
    public function it_can_toggle_like_from_liked_to_unliked()
    {
        $this->post->incrementPageLikes();

        $component = Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
        ]);

        // Simulate having liked the post
        $component->set('hasLiked', true);
        $component->set('likesCount', 1);

        $component->call('toggleLike')
            ->assertSet('hasLiked', false)
            ->assertSet('likesCount', 0)
            ->assertDispatched('like-toggled', [
                'contentId' => $this->post->id,
                'liked' => false,
                'likesCount' => 0,
            ]);

        $this->assertEquals(0, $this->post->fresh()->page_likes);
    }

    /** @test */
    public function it_throws_exception_for_model_without_likes_trait()
    {
        $user = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Content model must use HasPageLikes trait');

        Livewire::test(LikeButton::class, [
            'content' => $user,
            'lang' => 'en',
            'contentType' => 'users',
        ]);
    }

    /** @test */
    public function it_can_configure_display_options()
    {
        Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
            'showCount' => false,
            'size' => 'lg',
            'variant' => 'minimal',
        ])
            ->assertSet('showCount', false)
            ->assertSet('size', 'lg')
            ->assertSet('variant', 'minimal');
    }

    /** @test */
    public function it_returns_correct_size_classes()
    {
        $component = Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
            'size' => 'sm',
        ]);

        $this->assertEquals('text-sm px-2 py-1', $component->instance()->getSizeClasses());

        $component->set('size', 'lg');
        $this->assertEquals('text-lg px-4 py-3', $component->instance()->getSizeClasses());

        $component->set('size', 'md');
        $this->assertEquals('text-base px-3 py-2', $component->instance()->getSizeClasses());
    }

    /** @test */
    public function it_returns_correct_variant_classes()
    {
        $component = Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
            'variant' => 'minimal',
        ]);

        $this->assertEquals('bg-transparent hover:bg-gray-100 text-gray-600', $component->instance()->getVariantClasses());

        $component->set('variant', 'outline');
        $this->assertEquals('border border-gray-300 bg-white hover:bg-gray-50 text-gray-700', $component->instance()->getVariantClasses());

        $component->set('variant', 'default');
        $this->assertEquals('bg-gray-100 hover:bg-gray-200 text-gray-700', $component->instance()->getVariantClasses());
    }

    /** @test */
    public function it_initializes_like_state_from_cookie()
    {
        $cookieName = "liked_content_{$this->post->id}";

        $component = Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
        ])
            ->withCookies([$cookieName => 'true']);

        $component->call('initializeLikeState');
        $component->assertSet('hasLiked', true);
    }

    /** @test */
    public function it_handles_existing_likes_count()
    {
        $this->post->update(['page_likes' => 5]);

        Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
        ])
            ->assertSet('likesCount', 5);
    }

    /** @test */
    public function it_can_handle_multiple_toggles()
    {
        $component = Livewire::test(LikeButton::class, [
            'content' => $this->post,
            'lang' => 'en',
            'contentType' => 'posts',
        ]);

        // Like
        $component->call('toggleLike')
            ->assertSet('hasLiked', true)
            ->assertSet('likesCount', 1);

        // Unlike
        $component->call('toggleLike')
            ->assertSet('hasLiked', false)
            ->assertSet('likesCount', 0);

        // Like again
        $component->call('toggleLike')
            ->assertSet('hasLiked', true)
            ->assertSet('likesCount', 1);

        $this->assertEquals(1, $this->post->fresh()->page_likes);
    }
}
