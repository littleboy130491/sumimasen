<?php

namespace Littleboy130491\Sumimasen\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Littleboy130491\Sumimasen\Enums\CommentStatus;
use Littleboy130491\Sumimasen\Mail\CommentReplyNotification;
use Littleboy130491\Sumimasen\Mail\NewCommentNotification;
use Littleboy130491\Sumimasen\Models\Comment;
use Spatie\ResponseCache\Facades\ResponseCache;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        try {
            // Send admin notification for all new comments
            $this->sendAdminNotification($comment);

            // Send reply notification if comment is approved and is a reply
            if ($comment->status === CommentStatus::Approved && $comment->parent_id) {
                $this->sendReplyNotification($comment);
            }

            // Clear cache after successful creation
            $this->clearCacheForCommentable($comment);

        } catch (\Exception $e) {
            Log::error('Error in CommentObserver created method', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle the Comment "updated" event.
     */
    public function updated(Comment $comment): void
    {
        try {
            // Check if status changed to approved
            if ($comment->isDirty('status') && $comment->status === CommentStatus::Approved) {
                // Send reply notification if it's a reply
                if ($comment->parent_id) {
                    $this->sendReplyNotification($comment);
                }

                // Clear cache when comment gets approved (becomes visible)
                $this->clearCacheForCommentable($comment);
            }

            // Also clear cache if content was modified (for approved comments)
            if ($comment->status === CommentStatus::Approved && $comment->isDirty('content')) {
                $this->clearCacheForCommentable($comment);
            }

        } catch (\Exception $e) {
            Log::error('Error in CommentObserver updated method', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        try {
            $this->clearCacheForCommentable($comment);
        } catch (\Exception $e) {
            Log::error('Error in CommentObserver deleted method', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Comment "restored" event.
     */
    public function restored(Comment $comment): void
    {
        try {
            $this->clearCacheForCommentable($comment);
        } catch (\Exception $e) {
            Log::error('Error in CommentObserver restored method', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Comment "force deleted" event.
     */
    public function forceDeleted(Comment $comment): void
    {
        try {
            $this->clearCacheForCommentable($comment);
        } catch (\Exception $e) {
            Log::error('Error in CommentObserver forceDeleted method', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email notification to admin users about new comment.
     * Uses queue for better performance.
     */
    private function sendAdminNotification(Comment $comment): void
    {
        try {
            // Get admin users - make sure the role names match your system
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super_admin', 'super-admin']);
            })->get();

            if ($adminUsers->isEmpty()) {
                Log::warning('No admin users found for new comment notification', [
                    'comment_id' => $comment->id,
                ]);

                return;
            }

            // Queue the emails for better performance
            foreach ($adminUsers as $admin) {
                if ($admin->email) {
                    $this->queueEmail(
                        $admin->email,
                        new NewCommentNotification($comment)
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send admin comment notification', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email notification to parent comment author for replies.
     * Uses queue for better performance.
     */
    private function sendReplyNotification(Comment $comment): void
    {
        if (! $comment->parent_id) {
            return;
        }

        try {
            // Load parent comment with eager loading to avoid N+1
            $parentComment = $comment->parent()->with('commentable')->first();

            if (! $parentComment) {
                Log::warning('Parent comment not found for reply', [
                    'reply_id' => $comment->id,
                    'parent_id' => $comment->parent_id,
                ]);

                return;
            }

            if (empty($parentComment->email)) {
                Log::info('Parent comment has no email address, skipping reply notification', [
                    'reply_id' => $comment->id,
                    'parent_id' => $parentComment->id,
                ]);

                return;
            }

            // Don't send notification if replying to own comment
            if ($parentComment->email === $comment->email) {
                return;
            }

            $this->queueEmail(
                $parentComment->email,
                new CommentReplyNotification($comment, $parentComment)
            );

        } catch (\Exception $e) {
            Log::error('Failed to send comment reply notification', [
                'reply_id' => $comment->id,
                'parent_id' => $comment->parent_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Queue email for sending (or send immediately if queues not configured).
     */
    private function queueEmail(string $email, $mailable): void
    {
        try {
            if (config('queue.default') !== 'sync') {
                // Queue the email
                Mail::to($email)->queue($mailable);
            } else {
                // Send immediately if no queue configured
                Mail::to($email)->send($mailable);
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue/send email', [
                'email' => $email,
                'mailable' => get_class($mailable),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the frontend URL for the commentable model.
     */
    protected function getCommentableUrl(Comment $comment): ?string
    {
        try {
            $commentable = $comment->commentable;

            if (! $commentable) {
                return null;
            }

            // Try to use the model's own URL method if it exists
            if (method_exists($commentable, 'getUrl')) {
                return $commentable->getUrl();
            }

            if (method_exists($commentable, 'url')) {
                return $commentable->url();
            }

            // Fallback to CMS route generation
            return $this->generateCmsUrl($commentable);

        } catch (\Exception $e) {
            Log::warning('Failed to generate commentable URL', [
                'comment_id' => $comment->id,
                'commentable_type' => $comment->commentable_type,
                'commentable_id' => $comment->commentable_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate CMS URL for commentable model.
     */
    private function generateCmsUrl($commentable): ?string
    {
        $contentModels = config('cms.content_models', []);
        $commentableClass = get_class($commentable);

        // Find matching content type
        foreach ($contentModels as $key => $details) {
            if (isset($details['model']) && $details['model'] === $commentableClass) {
                if (! isset($commentable->slug)) {
                    Log::warning('Commentable model has no slug property', [
                        'class' => $commentableClass,
                        'id' => $commentable->id ?? 'unknown',
                    ]);

                    return null;
                }

                try {
                    return route('cms.single.content', [
                        'lang' => app()->getLocale(),
                        'content_type_key' => $key,
                        'content_slug' => $commentable->slug,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to generate CMS route', [
                        'content_type_key' => $key,
                        'slug' => $commentable->slug,
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Clear response cache for the commentable URL.
     */
    protected function clearCacheForCommentable(Comment $comment): void
    {
        try {
            // Only clear cache if ResponseCache is available
            if (! class_exists(ResponseCache::class)) {
                return;
            }

            $url = $this->getCommentableUrl($comment);

            if ($url) {
                ResponseCache::forget($url);

                // Also clear cache for different locales if multilingual
                if (config('cms.multilanguage_enabled', false)) {
                    $languages = array_keys(config('cms.language_available', []));
                    foreach ($languages as $lang) {
                        $localizedUrl = str_replace(
                            '/'.app()->getLocale().'/',
                            '/'.$lang.'/',
                            $url
                        );
                        ResponseCache::forget($localizedUrl);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::warning('Failed to clear cache for commentable', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
