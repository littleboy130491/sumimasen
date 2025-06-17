<?php

namespace Littleboy130491\Sumimasen\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Littleboy130491\Sumimasen\Mail\Concerns\HasViewFallback;

class AdminLoggedInNotification extends Mailable implements ShouldQueue
{
    use HasViewFallback, Queueable, SerializesModels;

    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Admin User Logged In: '.$this->user->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: $this->getViewWithFallback('emails.admin.loggedin'),
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'loginTime' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
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
