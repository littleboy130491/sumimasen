<?php

namespace Littleboy130491\Sumimasen\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Littleboy130491\Sumimasen\Models\Submission;

class FormSubmissionNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Submission $submission;

    /**
     * Create a new message instance.
     */
    public function __construct(Submission $submission)
    {
        $this->submission = $submission;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $submitterName = $this->submission->fields['name'] ?? 'Unknown';
        $subject = $this->submission->fields['subject'] ?? 'Contact Form Submission';

        return new Envelope(
            subject: 'New Contact Form Submission: '.$subject.' - '.$submitterName,
            replyTo: [
                $this->submission->fields['email'] ?? config('mail.from.address'),
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.form-submission',
            with: [
                'submitterName' => $this->submission->fields['name'] ?? 'Not provided',
                'submitterEmail' => $this->submission->fields['email'] ?? 'Not provided',
                'submitterPhone' => $this->submission->fields['phone'] ?? 'Not provided',
                'subject' => $this->submission->fields['subject'] ?? 'No subject',
                'message' => $this->submission->fields['message'] ?? 'No message',
                'submissionTime' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'ipAddress' => $this->submission->fields['ip_address'] ?? 'Unknown',
                'userAgent' => $this->submission->fields['user_agent'] ?? 'Unknown',
                'submissionId' => $this->submission->id,
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
