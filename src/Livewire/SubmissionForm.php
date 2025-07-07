<?php

namespace Littleboy130491\Sumimasen\Livewire;

use Anhskohbo\NoCaptcha\Facades\NoCaptcha;
use Illuminate\Support\Facades\Mail;
use Littleboy130491\Sumimasen\Mail\FormSubmissionNotification;
use Littleboy130491\Sumimasen\Models\Submission;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

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

    public $turnstile = '';

    public $showSuccess = false;

    public $formSubmitted = false;

    public function submit()
    {
        // Prevent multiple submissions
        if ($this->formSubmitted) {
            return;
        }

        $this->validate();

        // Validate bot protection based on configured type
        if ($this->isBotProtectionEnabled()) {
            $botProtectionType = $this->getBotProtectionType();

            if ($botProtectionType === 'captcha') {
                if (!NoCaptcha::verifyResponse($this->captcha, request()->ip())) {
                    $this->addError('captcha', __('sumimasen-cms::submission-form.captcha_error'));
                    return;
                }
            } elseif ($botProtectionType === 'turnstile') {
                if (!Turnstile::verify($this->turnstile)) {
                    $this->addError('turnstile', __('sumimasen-cms::submission-form.turnstile_error'));
                    return;
                }
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
            $adminEmail = config('cms.form_submission.admin_email');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new FormSubmissionNotification($submission));
            }

            // Mark form as submitted to prevent further submissions
            $this->formSubmitted = true;

            // Show success message
            $this->showSuccess = true;

            // Hide success message after 5 seconds
            $this->dispatch('hide-success-after-delay');

            // Reset bot protection widget if enabled
            if ($this->isBotProtectionEnabled()) {
                $botProtectionType = $this->getBotProtectionType();
                if ($botProtectionType === 'captcha') {
                    $this->dispatch('reset-captcha');
                } elseif ($botProtectionType === 'turnstile') {
                    $this->dispatch('reset-turnstile');
                }
            }

        } catch (\Exception $e) {
            // Set error message on component
            $this->addError('form', __('sumimasen-cms::submission-form.submission_error'));
        }
    }

    public function isCaptchaEnabled()
    {
        return !empty(env('NOCAPTCHA_SITEKEY')) && !empty(env('NOCAPTCHA_SECRET'));
    }

    public function isTurnstileEnabled()
    {
        return !empty(env('TURNSTILE_SITE_KEY')) && !empty(env('TURNSTILE_SECRET_KEY'));
    }

    public function isBotProtectionEnabled()
    {
        $type = $this->getBotProtectionType();
        return ($type === 'captcha' && $this->isCaptchaEnabled()) ||
            ($type === 'turnstile' && $this->isTurnstileEnabled());
    }

    public function getBotProtectionType()
    {
        return config('cms.bot_protection_type', 'captcha');
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
        return view('sumimasen-cms::livewire.submission-form');
    }
}
