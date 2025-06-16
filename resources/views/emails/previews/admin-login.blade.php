<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login Notification - Email Preview</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .email-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { color: #333; margin-bottom: 20px; }
        .content { color: #666; line-height: 1.6; }
        .details { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .details h3 { color: #333; margin-top: 0; }
        .details ul { margin: 0; padding-left: 20px; }
        .footer { color: #666; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="email-container">
        <h1 class="header">Admin User Logged In</h1>
        <p class="content">An admin user has logged into the system.</p>
        
        <div class="details">
            <h3>User Details</h3>
            <ul>
                <li><strong>Name:</strong> {{ $userName }}</li>
                <li><strong>Email:</strong> {{ $userEmail }}</li>
                <li><strong>Login Time:</strong> {{ $loginTime }} GMT+7</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>Thanks,<br>{{ config('app.name', 'Application') }}</p>
        </div>
    </div>
</body>
</html>