<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comment Reply Notification - Email Preview</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .email-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { color: #333; margin-bottom: 20px; }
        .content { color: #666; line-height: 1.6; }
        .details { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .reply-content { margin-top: 15px; padding: 15px; background: white; border-left: 4px solid #28a745; border-radius: 4px; }
        .button { text-align: center; margin: 30px 0; }
        .button a { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .footer { color: #666; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="email-container">
        <h1 class="header">{{ __('sumimasen-cms::emails.reply_notification_subject', ['name' => $parentCommentAuthorName]) }}</h1>
        <p class="content">{!! __('sumimasen-cms::emails.reply_notification_body_line1', ['reply_author_name' => $replyAuthorName, 'commentable_title' => $commentableTitle]) !!}</p>
        
        <div class="details">
            <p style="margin: 0 0 10px 0;">{!! __('sumimasen-cms::emails.reply_notification_reply_details', ['reply_date' => $replyDate]) !!}</p>
            
            <div class="reply-content">
                {{ $replyContent }}
            </div>
        </div>
        
        @if($commentableUrl !== '#')
        <div class="button">
            <a href="{{ $commentableUrl }}">{{ __('sumimasen-cms::emails.reply_notification_view_conversation_button') }}</a>
        </div>
        @else
        <p class="content">{{ __('sumimasen-cms::emails.reply_notification_view_conversation_text') }}</p>
        @endif
        
        <div class="footer">
            <p>{{ __('sumimasen-cms::emails.thanks') }}<br>{{ config('app.name', 'Application') }}</p>
        </div>
    </div>
</body>
</html>