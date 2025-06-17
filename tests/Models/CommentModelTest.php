<?php

namespace Littleboy130491\Sumimasen\Tests\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Enums\CommentStatus;
use Littleboy130491\Sumimasen\Models\Comment;
use Littleboy130491\Sumimasen\Models\Post;
use Littleboy130491\Sumimasen\Tests\TestCase;

class CommentModelTest extends TestCase
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
    public function it_can_create_a_comment()
    {
        $comment = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'content' => 'Great post!',
            'status' => CommentStatus::Approved,
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
        ]);
    }

    /** @test */
    public function it_belongs_to_commentable_model()
    {
        $comment = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'content' => 'Great post!',
        ]);

        $this->assertInstanceOf(Post::class, $comment->commentable);
        $this->assertEquals($this->post->id, $comment->commentable->id);
    }

    /** @test */
    public function it_can_have_a_parent_comment()
    {
        $parentComment = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'content' => 'First comment',
        ]);

        $childComment = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'content' => 'Reply to first comment',
            'parent_id' => $parentComment->id,
        ]);

        $this->assertInstanceOf(Comment::class, $childComment->parent);
        $this->assertEquals($parentComment->id, $childComment->parent->id);
    }

    /** @test */
    public function it_can_have_children_comments()
    {
        $parentComment = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'content' => 'Parent comment',
        ]);

        $child1 = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'content' => 'First reply',
            'parent_id' => $parentComment->id,
        ]);

        $child2 = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'content' => 'Second reply',
            'parent_id' => $parentComment->id,
        ]);

        $this->assertCount(2, $parentComment->children);
        $this->assertTrue($parentComment->children->contains($child1));
        $this->assertTrue($parentComment->children->contains($child2));
    }

    /** @test */
    public function it_can_get_children_recursively()
    {
        $parentComment = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'content' => 'Parent comment',
        ]);

        $child = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'content' => 'Child comment',
            'parent_id' => $parentComment->id,
        ]);

        $grandchild = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'content' => 'Grandchild comment',
            'parent_id' => $child->id,
        ]);

        $recursiveChildren = $parentComment->childrenRecursive;

        $this->assertCount(1, $recursiveChildren);
        $this->assertEquals($child->id, $recursiveChildren->first()->id);
        $this->assertCount(1, $recursiveChildren->first()->childrenRecursive);
        $this->assertEquals($grandchild->id, $recursiveChildren->first()->childrenRecursive->first()->id);
    }

    /** @test */
    public function it_casts_status_to_enum()
    {
        $comment = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'content' => 'Great post!',
            'status' => CommentStatus::Pending,
        ]);

        $this->assertInstanceOf(CommentStatus::class, $comment->status);
        $this->assertEquals(CommentStatus::Pending, $comment->status);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $comment = Comment::create([
            'commentable_id' => $this->post->id,
            'commentable_type' => Post::class,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'content' => 'Great post!',
        ]);

        $comment->delete();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        $this->assertNotNull($comment->fresh()->deleted_at);
    }
}
