<?php

namespace Littleboy130491\Sumimasen\Tests\Livewire;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Littleboy130491\Sumimasen\Livewire\SubmissionForm;
use Littleboy130491\Sumimasen\Mail\FormSubmissionNotification;
use Littleboy130491\Sumimasen\Models\Submission;
use Littleboy130491\Sumimasen\Tests\TestCase;
use Livewire\Livewire;

class SubmissionFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function it_can_render_submission_form_component()
    {
        Livewire::test(SubmissionForm::class)
            ->assertSuccessful()
            ->assertViewIs('sumimasen-cms::livewire.submission-form');
    }

    /** @test */
    public function it_initializes_with_empty_form_fields()
    {
        Livewire::test(SubmissionForm::class)
            ->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('message', '')
            ->assertSet('subject', '')
            ->assertSet('phone', '')
            ->assertSet('showSuccess', false)
            ->assertSet('formSubmitted', false);
    }

    /** @test */
    public function it_can_submit_form_with_valid_data()
    {
        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message with enough characters to pass validation.',
            'subject' => 'Test Subject',
            'phone' => '+1234567890',
        ];

        Livewire::test(SubmissionForm::class)
            ->set('name', $formData['name'])
            ->set('email', $formData['email'])
            ->set('message', $formData['message'])
            ->set('subject', $formData['subject'])
            ->set('phone', $formData['phone'])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('showSuccess', true)
            ->assertSet('formSubmitted', true)
            ->assertDispatched('hide-success-after-delay');

        $this->assertDatabaseHas('submissions', [
            'fields->name' => $formData['name'],
            'fields->email' => $formData['email'],
            'fields->message' => $formData['message'],
        ]);
    }

    /** @test */
    public function it_sends_email_notification_after_successful_submission()
    {
        config(['mail.from.address' => 'admin@example.com']);

        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('message', 'This is a test message with enough characters to pass validation.')
            ->call('submit');

        Mail::assertSent(FormSubmissionNotification::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test(SubmissionForm::class)
            ->set('name', '')
            ->set('email', '')
            ->set('message', '')
            ->call('submit')
            ->assertHasErrors([
                'name' => 'required',
                'email' => 'required',
                'message' => 'required',
            ]);
    }

    /** @test */
    public function it_validates_name_field()
    {
        Livewire::test(SubmissionForm::class)
            ->set('name', 'a') // Too short
            ->set('email', 'john@example.com')
            ->set('message', 'Valid message with enough characters.')
            ->call('submit')
            ->assertHasErrors(['name' => 'min']);

        Livewire::test(SubmissionForm::class)
            ->set('name', str_repeat('a', 256)) // Too long
            ->set('email', 'john@example.com')
            ->set('message', 'Valid message with enough characters.')
            ->call('submit')
            ->assertHasErrors(['name' => 'max']);
    }

    /** @test */
    public function it_validates_email_field()
    {
        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'invalid-email')
            ->set('message', 'Valid message with enough characters.')
            ->call('submit')
            ->assertHasErrors(['email' => 'email']);

        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', str_repeat('a', 250).'@example.com') // Too long
            ->set('message', 'Valid message with enough characters.')
            ->call('submit')
            ->assertHasErrors(['email' => 'max']);
    }

    /** @test */
    public function it_validates_message_field()
    {
        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('message', 'Short') // Too short
            ->call('submit')
            ->assertHasErrors(['message' => 'min']);

        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('message', str_repeat('a', 1001)) // Too long
            ->call('submit')
            ->assertHasErrors(['message' => 'max']);
    }

    /** @test */
    public function it_validates_optional_fields()
    {
        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('message', 'Valid message with enough characters.')
            ->set('subject', str_repeat('a', 256)) // Too long
            ->call('submit')
            ->assertHasErrors(['subject' => 'max']);

        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('message', 'Valid message with enough characters.')
            ->set('phone', str_repeat('1', 51)) // Too long
            ->call('submit')
            ->assertHasErrors(['phone' => 'max']);
    }

    /** @test */
    public function it_performs_real_time_validation()
    {
        $component = Livewire::test(SubmissionForm::class);

        $component->set('name', 'a')
            ->assertHasErrors(['name' => 'min']);

        $component->set('name', 'John Doe')
            ->assertHasNoErrors(['name']);

        $component->set('email', 'invalid-email')
            ->assertHasErrors(['email' => 'email']);

        $component->set('email', 'john@example.com')
            ->assertHasNoErrors(['email']);
    }

    /** @test */
    public function it_prevents_multiple_submissions()
    {
        $component = Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('message', 'Valid message with enough characters.')
            ->call('submit');

        // Try to submit again
        $component->call('submit');

        // Should only have one submission in database
        $this->assertEquals(1, Submission::count());
    }

    /** @test */
    public function it_can_hide_success_message()
    {
        Livewire::test(SubmissionForm::class)
            ->set('showSuccess', true)
            ->call('hideSuccess')
            ->assertSet('showSuccess', false);
    }

    /** @test */
    public function it_stores_submission_metadata()
    {
        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('message', 'Valid message with enough characters.')
            ->call('submit');

        $submission = Submission::first();

        $this->assertArrayHasKey('submitted_at', $submission->fields);
        $this->assertArrayHasKey('ip_address', $submission->fields);
        $this->assertArrayHasKey('user_agent', $submission->fields);
        $this->assertNotNull($submission->fields['submitted_at']);
        $this->assertNotNull($submission->fields['ip_address']);
        $this->assertNotNull($submission->fields['user_agent']);
    }

    /** @test */
    public function it_detects_captcha_configuration()
    {
        $component = Livewire::test(SubmissionForm::class);

        // Test with no captcha config
        config(['captcha.sitekey' => '', 'captcha.secret' => '']);
        $this->assertFalse($component->instance()->isCaptchaEnabled());

        // Test with captcha config
        config(['captcha.sitekey' => 'test-site-key', 'captcha.secret' => 'test-secret']);
        $this->assertTrue($component->instance()->isCaptchaEnabled());
    }

    /** @test */
    public function it_handles_submission_exceptions_gracefully()
    {
        // Mock Submission::create to throw exception
        $this->mock(Submission::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new \Exception('Database error'));
        });

        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('message', 'Valid message with enough characters.')
            ->call('submit')
            ->assertHasErrors(['form']);
    }

    /** @test */
    public function it_can_submit_form_with_only_required_fields()
    {
        Livewire::test(SubmissionForm::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('message', 'This is a test message with enough characters.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('showSuccess', true);

        $this->assertDatabaseHas('submissions', [
            'fields->name' => 'John Doe',
            'fields->email' => 'john@example.com',
        ]);
    }
}
