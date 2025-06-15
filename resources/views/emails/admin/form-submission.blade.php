<x-sumimasen-cms::mail::message>
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

<x-sumimasen-cms::mail::panel>
    {{ $message }}
</x-sumimasen-cms::mail::panel>

## Technical Information

- **IP Address:** {{ $ipAddress }}
- **User Agent:** {{ $userAgent }}

<x-sumimasen-cms::mail::button :url="config('app.url') . '/admin/submissions/' . $submissionId">
    View in Admin Panel
</x-sumimasen-cms::mail::button>

---

**Reply Information:**
You can reply directly to this email to respond to {{ $submitterName }} at {{ $submitterEmail }}.

Thanks,<br>
{{ config('app.name') }} Contact Form System
</x-sumimasen-cms::mail::message>
