<?php

namespace Littleboy130491\Sumimasen\Tests\Mail;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Mail\FormSubmissionNotification;
use Littleboy130491\Sumimasen\Models\Submission;
use Littleboy130491\Sumimasen\Tests\TestCase;

class FormSubmissionNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->submission = Submission::create([
            'fields' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'subject' => 'Test Inquiry',
                'message' => 'This is a test message from the contact form.',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 Test Browser',
                'submitted_at' => now()->toISOString(),
            ],
        ]);
    }

    /** @test */
    public function it_can_create_mail_notification()
    {
        $mail = new FormSubmissionNotification($this->submission);

        $this->assertInstanceOf(FormSubmissionNotification::class, $mail);
        $this->assertEquals($this->submission->id, $mail->submission->id);
    }

    /** @test */
    public function it_generates_correct_email_envelope()
    {
        $mail = new FormSubmissionNotification($this->submission);
        $envelope = $mail->envelope();

        $this->assertEquals(
            'New Contact Form Submission: Test Inquiry - John Doe',
            $envelope->subject
        );

        $this->assertContains('john@example.com', $envelope->replyTo);
    }

    /** @test */
    public function it_handles_missing_subject_in_envelope()
    {
        $submissionWithoutSubject = Submission::create([
            'fields' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'message' => 'Test message without subject',
            ],
        ]);

        $mail = new FormSubmissionNotification($submissionWithoutSubject);
        $envelope = $mail->envelope();

        $this->assertEquals(
            'New Contact Form Submission: Contact Form Submission - Jane Doe',
            $envelope->subject
        );
    }

    /** @test */
    public function it_handles_missing_name_in_envelope()
    {
        $submissionWithoutName = Submission::create([
            'fields' => [
                'email' => 'anonymous@example.com',
                'subject' => 'Anonymous Inquiry',
                'message' => 'Test message without name',
            ],
        ]);

        $mail = new FormSubmissionNotification($submissionWithoutName);
        $envelope = $mail->envelope();

        $this->assertEquals(
            'New Contact Form Submission: Anonymous Inquiry - Unknown',
            $envelope->subject
        );
    }

    /** @test */
    public function it_uses_fallback_reply_to_when_email_missing()
    {
        config(['mail.from.address' => 'noreply@example.com']);

        $submissionWithoutEmail = Submission::create([
            'fields' => [
                'name' => 'John Doe',
                'message' => 'Test message without email',
            ],
        ]);

        $mail = new FormSubmissionNotification($submissionWithoutEmail);
        $envelope = $mail->envelope();

        $this->assertContains('noreply@example.com', $envelope->replyTo);
    }

    /** @test */
    public function it_generates_correct_email_content()
    {
        $mail = new FormSubmissionNotification($this->submission);
        $content = $mail->content();

        $this->assertStringContainsString('emails.admin.form-submission', $content->markdown);

        // Check that all expected data is passed to the view
        $expectedData = [
            'submitterName',
            'submitterEmail',
            'submitterPhone',
            'subject',
            'message',
            'submissionTime',
            'ipAddress',
            'userAgent',
            'submissionId',
        ];

        foreach ($expectedData as $key) {
            $this->assertArrayHasKey($key, $content->with);
        }

        // Check specific values
        $this->assertEquals('John Doe', $content->with['submitterName']);
        $this->assertEquals('john@example.com', $content->with['submitterEmail']);
        $this->assertEquals('+1234567890', $content->with['submitterPhone']);
        $this->assertEquals('Test Inquiry', $content->with['subject']);
        $this->assertEquals('This is a test message from the contact form.', $content->with['message']);
        $this->assertEquals($this->submission->id, $content->with['submissionId']);
    }

    /** @test */
    public function it_handles_missing_optional_fields_in_content()
    {
        $minimalSubmission = Submission::create([
            'fields' => [
                'name' => 'Minimal User',
                'email' => 'minimal@example.com',
                'message' => 'Minimal message',
            ],
        ]);

        $mail = new FormSubmissionNotification($minimalSubmission);
        $content = $mail->content();

        $this->assertEquals('Minimal User', $content->with['submitterName']);
        $this->assertEquals('minimal@example.com', $content->with['submitterEmail']);
        $this->assertEquals('Not provided', $content->with['submitterPhone']);
        $this->assertEquals('No subject', $content->with['subject']);
        $this->assertEquals('Minimal message', $content->with['message']);
        $this->assertEquals('Unknown', $content->with['ipAddress']);
        $this->assertEquals('Unknown', $content->with['userAgent']);
    }

    /** @test */
    public function it_handles_completely_empty_fields()
    {
        $emptySubmission = Submission::create([
            'fields' => [],
        ]);

        $mail = new FormSubmissionNotification($emptySubmission);
        $content = $mail->content();

        $this->assertEquals('Not provided', $content->with['submitterName']);
        $this->assertEquals('Not provided', $content->with['submitterEmail']);
        $this->assertEquals('Not provided', $content->with['submitterPhone']);
        $this->assertEquals('No subject', $content->with['subject']);
        $this->assertEquals('No message', $content->with['message']);
        $this->assertEquals('Unknown', $content->with['ipAddress']);
        $this->assertEquals('Unknown', $content->with['userAgent']);
    }

    /** @test */
    public function it_returns_empty_attachments_array()
    {
        $mail = new FormSubmissionNotification($this->submission);
        $attachments = $mail->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }

    /** @test */
    public function it_implements_should_queue_interface()
    {
        $mail = new FormSubmissionNotification($this->submission);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mail);
    }

    /** @test */
    public function it_uses_queueable_trait()
    {
        $mail = new FormSubmissionNotification($this->submission);

        $this->assertTrue(method_exists($mail, 'onQueue'));
        $this->assertTrue(method_exists($mail, 'onConnection'));
        $this->assertTrue(method_exists($mail, 'delay'));
    }

    /** @test */
    public function it_includes_submission_time_in_content()
    {
        $mail = new FormSubmissionNotification($this->submission);
        $content = $mail->content();

        $this->assertArrayHasKey('submissionTime', $content->with);
        $this->assertNotEmpty($content->with['submissionTime']);
        $this->assertIsString($content->with['submissionTime']);
    }

    /** @test */
    public function it_preserves_submission_reference()
    {
        $mail = new FormSubmissionNotification($this->submission);

        // The mail should maintain a reference to the original submission
        $this->assertSame($this->submission, $mail->submission);
        $this->assertEquals($this->submission->id, $mail->submission->id);
        $this->assertEquals($this->submission->fields, $mail->submission->fields);
    }
}