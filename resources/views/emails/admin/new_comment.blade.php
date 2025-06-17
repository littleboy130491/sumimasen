@component('mail::message')
# {{ __('sumimasen-cms::emails.new_comment_subject', ['commentable_title' => $commentableTitle]) }}

{{ __('sumimasen-cms::emails.new_comment_body_line1', ['commentable_type' => $commentableType, 'commentable_title' => $commentableTitle]) }}

**{{ __('sumimasen-cms::emails.new_comment_details') }}**
- **{{ __('sumimasen-cms::emails.new_comment_author') }}** {{ $commentAuthorName }} ({{ $commentAuthorEmail }})
- **{{ __('sumimasen-cms::emails.new_comment_posted_at') }}** {{ $postedAt }} GMT+7
- **{{ __('sumimasen-cms::emails.new_comment_content') }}**
@component('mail::panel')
{{ $commentContent }}
@endcomponent

@if($commentUrl !== '#')
@component('mail::button', ['url' => $commentUrl])
{{ __('sumimasen-cms::emails.new_comment_view_button') }}
@endcomponent
@else
{{ __('sumimasen-cms::emails.new_comment_view_text') }}
@endif

{{ __('sumimasen-cms::emails.thanks') }}<br>
{{ config('app.name') }}
@endcomponent