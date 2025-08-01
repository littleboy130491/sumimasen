@component('mail::message')
# {{ __('sumimasen-cms::emails.approval_notification_subject') }}

{{ __('sumimasen-cms::emails.approval_notification_greeting', ['name' => $commentAuthorName]) }}

{{ __('sumimasen-cms::emails.approval_notification_body_line1', ['commentable_title' => $commentableTitle]) }}

## {{ __('sumimasen-cms::emails.approval_notification_your_comment') }}
@component('mail::panel')
{{ $commentContent }}
@endcomponent

{{ __('sumimasen-cms::emails.approval_notification_view_comment_text') }}

@if($commentableUrl !== '#')
@component('mail::button', ['url' => $commentableUrl])
{{ __('sumimasen-cms::emails.approval_notification_view_comment_button') }}
@endcomponent
@else
{{ __('sumimasen-cms::emails.approval_notification_view_comment_text') }}
@endif

**{{ __('sumimasen-cms::emails.approval_notification_approved_on', ['approved_date' => $approvedDate]) }}**

{{ __('sumimasen-cms::emails.approval_notification_thanks_message') }}

{{ __('sumimasen-cms::emails.thanks') }}<br>
{{ config('app.name') }}
@endcomponent
