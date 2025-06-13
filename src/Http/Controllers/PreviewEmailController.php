<?php

namespace Littleboy130491\Sumimasen\Http\Controllers;

use App\Models\User;
use Littleboy130491\Sumimasen\Models\Comment;

class PreviewEmailController extends Controller
{
    public array $previewRoutes = [
        'preview-login-notification' => 'Admin Login Notification',
        'preview-comment-notification' => 'New Comment Notification',
        'preview-comment-reply-notification' => 'Comment Reply Notification',
    ];

    public function emailInfo($lang)
    {
        $availablePreviews = [];
        foreach ($this->previewRoutes as $slug => $description) {
            $availablePreviews[] = [
                'url' => route('preview.email.detail', compact('lang', 'slug')),
                'description' => $description,
                'slug' => $slug,
            ];
        }

        // Generate HTML output
        $html = '<h1>Available Email Previews</h1>';
        $html .= '<ul>';
        foreach ($availablePreviews as $preview) {
            $html .= '<li><a href="'.e($preview['url']).'">'.e($preview['description']).'</a> (Slug: <code>'.e($preview['slug']).'</code>)</li>';
        }
        $html .= '</ul>';

        return response($html)
            ->header('Content-Type', 'text/html');
    }

    public function emailTemplate($lang, $slug)
    {

        switch ($slug) {
            case 'preview-login-notification':
                $user = User::first();
                if (! $user) {
                    abort(404, 'No users found in the database to preview the Admin Login Notification email.');
                }

                return new \Littleboy130491\Sumimasen\Mail\AdminLoggedInNotification($user);

            case 'preview-comment-notification':
                $comment = Comment::first();
                if (! $comment) {
                    abort(404, 'No comments found in the database to preview the New Comment Notification email.');
                }

                return new \Littleboy130491\Sumimasen\Mail\NewCommentNotification($comment);

            case 'preview-comment-reply-notification':
                $comment = Comment::first();
                if (! $comment) {
                    abort(404, 'No comments found to preview the Comment Reply Notification email.');
                }
                // Ensure the comment has a parent for this specific notification
                if (! $comment->parent) {
                    // Attempt to find a comment that has a parent for demonstration purposes
                    $commentWithParent = Comment::whereNotNull('parent_id')->first();
                    if ($commentWithParent) {
                        $comment = $commentWithParent;
                    } else {
                        abort(404, 'No comment with a parent reply found to preview the Comment Reply Notification email. Please create one.');
                    }
                }

                return new \Littleboy130491\Sumimasen\Mail\CommentReplyNotification($comment, $comment->parent);

            default:
                abort(404, 'Email preview not found for slug: '.e($slug));
        }
    }
}
