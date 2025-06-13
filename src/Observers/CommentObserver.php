<?php

namespace Littleboy130491\Sumimasen\Observers;

use Littleboy130491\Sumimasen\Models\Comment;
use Littleboy130491\Sumimasen\Models\User;
use Littleboy130491\Sumimasen\Mail\NewCommentNotification;
use Littleboy130491\Sumimasen\Mail\CommentReplyNotification;
use Littleboy130491\Sumimasen\Enums\CommentStatus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Spatie\ResponseCache\Facades\ResponseCache;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        try {
            $this->sendAdminNotification($comment);

            // if default status is 'approved', send email when replying
            if ($comment->status === CommentStatus::Approved && $comment->parent_id) {
                $this->sendReplyNotification($comment);
            }

        } catch (\Exception $e) {
            Log::error('Error in CommentObserver created method for comment ID: ' . $comment->id . '. Error: ' . $e->getMessage());

        }

        // Clear the cache for the commentable URL
        $this->clearCacheForCommentable($comment);
    }

    /**
     * Handle the Comment "updated" event.
     */
    public function updated(Comment $comment): void
    {
        // Check if the status was changed to 'approved' and it's a reply
        if ($comment->isDirty('status') && $comment->status === CommentStatus::Approved && $comment->parent_id) {
            $this->sendReplyNotification($comment);

            // Clear the cache for the commentable URL
            $this->clearCacheForCommentable($comment);
        }
    }


    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        // Clear the cache for the commentable URL
        $this->clearCacheForCommentable($comment);
    }

    /**
     * Handle the Comment "restored" event.
     */
    public function restored(Comment $comment): void
    {
        // Clear the cache for the commentable URL
        $this->clearCacheForCommentable($comment);
    }

    /**
     * Handle the Comment "force deleted" event.
     */
    public function forceDeleted(Comment $comment): void
    {
        //
    }

    /**
     * Send email notification to all admin users.
     */
    private function sendAdminNotification(Comment $comment): void
    {
        try {
            $adminUsers = User::role(['admin', 'super_admin'])->get();

            if ($adminUsers->isEmpty()) {
                Log::warning('No admin users found to send new comment notification for comment ID: ' . $comment->id);
                return;
            }

            foreach ($adminUsers as $admin) {
                Mail::to($admin->email)->send(new NewCommentNotification($comment));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin new comment notification for comment ID: ' . $comment->id . '. Error: ' . $e->getMessage());
        }
    }

    /**
     * Send email notification to the parent comment author if it's a reply.
     */
    private function sendReplyNotification(Comment $comment): void
    {
        if (!$comment->parent_id) {
            return;
        }

        try {
            $parentComment = $comment->parent; // Eloquent relationship

            if ($parentComment && !empty($parentComment->email)) {
                Mail::to($parentComment->email)->send(new CommentReplyNotification($comment, $parentComment));
                // Log::info('Comment reply notification sent to ' . $parentComment->email . ' for reply ID: ' . $comment->id);
            } elseif ($parentComment && empty($parentComment->email)) {
                // Log::info('Parent comment (ID: ' . $parentComment->id . ') for reply (ID: ' . $comment->id . ') does not have an email address. No notification sent.');
            } elseif (!$parentComment) {
                // Log::warning('Parent comment not found for reply ID: ' . $comment->id . '. No notification sent.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to send comment reply notification for reply ID: ' . $comment->id . '. Error: ' . $e->getMessage());
        }
    }
    protected function getCommentableUrl(Comment $comment): ?string
    {
        $commentable = $comment->commentable;

        if (!$commentable) {
            return null;
        }

        $contentModels = config('cms.content_models', []);
        $commentableClass = get_class($commentable);
        $contentTypeKey = null;

        foreach ($contentModels as $key => $details) {
            if (isset($details['model']) && $details['model'] === $commentableClass) {
                $contentTypeKey = $key;
                break;
            }
        }

        if ($contentTypeKey && isset($commentable->slug)) {
            try {
                return route('cms.single.content', [
                    'lang' => app()->getLocale(),
                    'content_type_key' => $contentTypeKey,
                    'content_slug' => $commentable->slug,
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to generate commentable URL via route: ' . $e->getMessage());
            }
        }

        return null;
    }

    protected function clearCacheForCommentable(Comment $comment)
    {
        $url = $this->getCommentableUrl($comment);
        if ($url) {
            ResponseCache::forget($url);
        }
    }

}