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
                    // Create mockup user data
                    $user = new User([
                        'name' => 'John Doe',
                        'email' => 'john.doe@example.com',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $mail = new \Littleboy130491\Sumimasen\Mail\AdminLoggedInNotification($user);
                return $mail->render();

            case 'preview-comment-notification':
                $comment = Comment::first();
                if (! $comment) {
                    // Create mockup comment data
                    $comment = new Comment([
                        'name' => 'Jane Smith',
                        'email' => 'jane.smith@example.com',
                        'content' => 'This is a sample comment for email preview purposes. It demonstrates how the comment notification email will look.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $mail = new \Littleboy130491\Sumimasen\Mail\NewCommentNotification($comment);
                return $mail->render();

            case 'preview-comment-reply-notification':
                $comment = Comment::first();
                if (! $comment) {
                    // Create mockup parent comment
                    $parentComment = new Comment([
                        'name' => 'Original Commenter',
                        'email' => 'original@example.com',
                        'content' => 'This is the original comment that was replied to.',
                        'created_at' => now()->subHour(),
                        'updated_at' => now()->subHour(),
                    ]);

                    // Create mockup reply comment
                    $comment = new Comment([
                        'name' => 'Reply Author',
                        'email' => 'reply@example.com',
                        'content' => 'This is a reply to the original comment for email preview purposes.',
                        'parent_id' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $comment->setRelation('parent', $parentComment);
                } else {
                    // Ensure the comment has a parent for this specific notification
                    if (! $comment->parent) {
                        // Attempt to find a comment that has a parent for demonstration purposes
                        $commentWithParent = Comment::whereNotNull('parent_id')->first();
                        if ($commentWithParent) {
                            $comment = $commentWithParent;
                        } else {
                            // Create mockup parent comment
                            $parentComment = new Comment([
                                'name' => 'Original Commenter',
                                'email' => 'original@example.com',
                                'content' => 'This is the original comment that was replied to.',
                                'created_at' => now()->subHour(),
                                'updated_at' => now()->subHour(),
                            ]);
                            $comment->setRelation('parent', $parentComment);
                        }
                    }
                }

                $mail = new \Littleboy130491\Sumimasen\Mail\CommentReplyNotification($comment, $comment->parent);
                return $mail->render();

            default:
                abort(404, 'Email preview not found for slug: '.e($slug));
        }
    }
}
