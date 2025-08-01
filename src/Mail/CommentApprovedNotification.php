<?php

namespace Littleboy130491\Sumimasen\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Littleboy130491\Sumimasen\Mail\Concerns\HasViewFallback;
use Littleboy130491\Sumimasen\Models\Comment;

class CommentApprovedNotification extends Mailable implements ShouldQueue
{
    use HasViewFallback, Queueable, SerializesModels;

    public Comment $comment;

    /**
     * Create a new message instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your comment has been approved on "' . (
                optional($this->comment->commentable)->title ?? 'a post'
            ) . '"',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $commentableUrl = '#';
        $commentable = $this->comment->commentable;

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
                    ]) . '#comment-' . $this->comment->id;
                } catch (\Exception $e) {
                    \Log::warning('Failed to generate commentable URL via route: ' . $e->getMessage());
                }
            }
        }

        return new Content(
            markdown: $this->getViewWithFallback('emails.comment.approved_notification'),
            with: [
                'commentAuthorName' => $this->comment->name,
                'commentContent' => $this->comment->content,
                'commentableTitle' => optional($commentable)->title ?? 'the post',
                'commentableUrl' => $commentableUrl,
                'approvedDate' => $this->comment->updated_at
                    ? $this->comment->updated_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
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