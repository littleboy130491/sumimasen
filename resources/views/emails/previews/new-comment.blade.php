<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Comment Notification - Email Preview</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .email-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { color: #333; margin-bottom: 20px; }
        .content { color: #666; line-height: 1.6; }
        .details { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .details h3 { color: #333; margin-top: 0; }
        .details ul { margin: 0; padding-left: 20px; }
        .comment-content { margin-top: 15px; padding: 15px; background: white; border-left: 4px solid #007bff; border-radius: 4px; }
        .footer { color: #666; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="email-container">
        <h1 class="header">New Comment Posted: {{ $commentableTitle }}</h1>
        <p class="content">A new comment has been posted on your {{ $commentableType }} titled "{{ $commentableTitle }}".</p>
        
        <div class="details">
            <h3>Comment Details</h3>
            <ul>
                <li><strong>Author:</strong> {{ $commentAuthorName }} ({{ $commentAuthorEmail }})</li>
                <li><strong>Posted at:</strong> {{ $postedAt }} GMT+7</li>
            </ul>
            
            <div class="comment-content">
                <strong>Comment:</strong><br>
                {{ $commentContent }}
            </div>
        </div>
        
        @if($commentUrl !== '#')
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $commentUrl }}" style="display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">
                View Comment
            </a>
        </div>
        @endif
        
        <div class="footer">
            <p>Thanks,<br>{{ config('app.name', 'Application') }}</p>
        </div>
    </div>
</body>
</html>