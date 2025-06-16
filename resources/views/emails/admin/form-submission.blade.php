@component('mail::message')
# New Contact Form Submission

A new contact form submission has been received on your website.

## Submission Details

**Submitter Information:**
- **Name:** {{ $submitterName }}
- **Email:** {{ $submitterEmail }}
- **Phone:** {{ $submitterPhone }}

**Message Details:**
- **Subject:** {{ $subject }}
- **Submission Time:** {{ $submissionTime }} (Asia/Jakarta)
- **Submission ID:** #{{ $submissionId }}

## Message Content

@component('mail::panel')
    {{ $message }}
@endcomponent

## Technical Information

- **IP Address:** {{ $ipAddress }}
- **User Agent:** {{ $userAgent }}

@component('mail::button', ['url' => "config('app.url') . '/admin/submissions/' . $submissionId"])
    View in Admin Panel
@endcomponent

---

**Reply Information:**
You can reply directly to this email to respond to {{ $submitterName }} at {{ $submitterEmail }}.

Thanks,<br>
{{ config('app.name') }} Contact Form System
@endcomponent
