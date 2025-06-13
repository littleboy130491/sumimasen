<?php

namespace Littleboy130491\Sumimasen\Mail;

use Littleboy130491\Sumimasen\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CommentReplyNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Comment $reply;
    public Comment $parentComment;

    /**
     * Create a new message instance.
     */
    public function __construct(Comment $reply, Comment $parentComment)
    {
        $this->reply = $reply;
        $this->parentComment = $parentComment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Someone replied to your comment on "' . (
                optional($this->parentComment->commentable)->title ?? 'a post'
            ) . '"',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $commentableUrl = '#';
        $commentable = $this->parentComment->commentable;

        if ($commentable) {
            // Find the content type key from config
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
                    $commentableUrl = route('cms.single.content', [
                        'lang' => app()->getLocale(),
                        'content_type_key' => $contentTypeKey,
                        'content_slug' => $commentable->slug,
                    ]) . '#comment-' . $this->reply->id;
                } catch (\Exception $e) {
                    \Log::warning('Failed to generate commentable URL via route: ' . $e->getMessage());
                }
            }
        }

        return new Content(
            markdown: 'emails.comment.reply_notification',
            with: [
                'parentCommentAuthorName' => $this->parentComment->name,
                'replyAuthorName' => $this->reply->name,
                'replyContent' => $this->reply->content,
                'commentableTitle' => optional($commentable)->title ?? 'the post',
                'commentableUrl' => $commentableUrl,
                'replyDate' => $this->reply->created_at
                    ? $this->reply->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                    : now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
