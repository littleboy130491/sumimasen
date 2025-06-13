@component('mail::message')
# {{ __('emails.reply_notification_subject', ['name' => $parentCommentAuthorName]) }}

{{ __('emails.reply_notification_body_line1', ['reply_author_name' => $replyAuthorName, 'commentable_title' => $commentableTitle]) }}

{{ __('emails.reply_notification_reply_details', ['reply_date' => $replyDate]) }}
@component('mail::panel')
{{ $replyContent }}
@endcomponent

@if($commentableUrl !== '#')
@component('mail::button', ['url' => $commentableUrl])
{{ __('emails.reply_notification_view_conversation_button') }}
@endcomponent
@else
{{ __('emails.reply_notification_view_conversation_text') }}
@endif

{{ __('emails.thanks') }}<br>
{{ config('app.name') }}
@endcomponent