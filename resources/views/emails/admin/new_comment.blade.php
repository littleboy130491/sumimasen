@component('mail::message')
# {{ __('emails.new_comment_subject', ['commentable_title' => $commentableTitle]) }}

{{ __('emails.new_comment_body_line1', ['commentable_type' => $commentableType, 'commentable_title' => $commentableTitle]) }}

**{{ __('emails.new_comment_details') }}**
- **{{ __('emails.new_comment_author') }}** {{ $commentAuthorName }} ({{ $commentAuthorEmail }})
- **{{ __('emails.new_comment_posted_at') }}** {{ $postedAt }} GMT+7
- **{{ __('emails.new_comment_content') }}**
@component('mail::panel')
{{ $commentContent }}
@endcomponent

@if($commentUrl !== '#')
@component('mail::button', ['url' => $commentUrl])
{{ __('emails.new_comment_view_button') }}
@endcomponent
@else
{{ __('emails.new_comment_view_text') }}
@endif

{{ __('emails.thanks') }}<br>
{{ config('app.name') }}
@endcomponent