<?php

namespace Littleboy130491\Sumimasen\Livewire;

use Anhskohbo\NoCaptcha\Facades\NoCaptcha;
use Illuminate\Support\Facades\Mail;
use Littleboy130491\Sumimasen\Mail\FormSubmissionNotification;
use Littleboy130491\Sumimasen\Models\Submission;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SubmissionForm extends Component
{
    #[Validate('required|string|min:2|max:255')]
    public $name = '';

    #[Validate('required|email|max:255')]
    public $email = '';

    #[Validate('required|string|min:10|max:1000')]
    public $message = '';

    #[Validate('nullable|string|max:255')]
    public $subject = '';

    #[Validate('nullable|string|max:50')]
    public $phone = '';

    public $captcha = '';

    public $showSuccess = false;

    public $formSubmitted = false;

    public function submit()
    {
        // Prevent multiple submissions
        if ($this->formSubmitted) {
            return;
        }

        $this->validate();

        // Validate CAPTCHA only if enabled
        if ($this->isCaptchaEnabled()) {
            if (! NoCaptcha::verifyResponse($this->captcha, request()->ip())) {
                $this->addError('captcha', __('submission-form.captcha_error'));

                return;
            }
        }

        try {
            // Create submission with all form data
            $submission = Submission::create([
                'fields' => [
                    'name' => $this->name,
                    'email' => $this->email,
                    'message' => $this->message,
                    'subject' => $this->subject,
                    'phone' => $this->phone,
                    'submitted_at' => now()->toISOString(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            // Send email notification to admin
            $adminEmail = env('MAIL_ADMIN_EMAIL', config('mail.from.address'));
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new FormSubmissionNotification($submission));
            }

            // Mark form as submitted to prevent further submissions
            $this->formSubmitted = true;

            // Show success message
            $this->showSuccess = true;

            // Hide success message after 5 seconds
            $this->dispatch('hide-success-after-delay');

            // Reset captcha widget if enabled
            if ($this->isCaptchaEnabled()) {
                $this->dispatch('reset-captcha');
            }

        } catch (\Exception $e) {
            // Set error message on component
            $this->addError('form', __('submission-form.submission_error'));
        }
    }

    public function isCaptchaEnabled()
    {
        return ! empty(config('captcha.sitekey')) && ! empty(config('captcha.secret'));
    }

    public function hideSuccess()
    {
        $this->showSuccess = false;
    }

    public function updated($propertyName)
    {
        // Real-time validation
        $this->validateOnly($propertyName);
    }

    public function render()
    {
        return view('livewire.submission-form');
    }
}
