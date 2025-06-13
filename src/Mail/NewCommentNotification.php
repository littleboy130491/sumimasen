<?php

namespace Littleboy130491\Sumimasen\Mail;

use Littleboy130491\Sumimasen\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCommentNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
            subject: 'New Comment Posted: ' . $this->comment->commentable->title, // Assuming commentable has a title
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Attempt to get the admin URL for the comment.
        // This might need adjustment based on your Filament resource setup.
        $commentUrl = '#'; // Default URL if specific one can't be generated
        if (class_exists(\Littleboy130491\Sumimasen\Filament\Resources\CommentResource::class)) {
            try {
                // Ensure the comment has an ID before trying to generate a URL
                if ($this->comment->exists && $this->comment->id) {
                    $commentUrl = \Littleboy130491\Sumimasen\Filament\Resources\CommentResource::getUrl('edit', ['record' => $this->comment]);
                }
            } catch (\Exception $e) {
                // Log error or handle if URL generation fails
            }
        }

        return new Content(
            markdown: 'emails.admin.new_comment',
            with: [
                'commentAuthorName' => $this->comment->name,
                'commentAuthorEmail' => $this->comment->email,
                'commentContent' => $this->comment->content,
                'commentUrl' => $commentUrl,
                'commentableTitle' => $this->comment->commentable->title ?? 'N/A', // Provide a fallback
                'commentableType' => class_basename($this->comment->commentable_type),
                'postedAt' => $this->comment->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
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