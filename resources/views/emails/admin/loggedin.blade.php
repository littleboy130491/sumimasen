<x-mail::message>
# {{ __('emails.admin_loggedin_subject') }}

{{ __('emails.admin_loggedin_body_line1') }}

**{{ __('emails.admin_loggedin_user_details') }}**
- **{{ __('emails.admin_loggedin_name') }}** {{ $userName }}
- **{{ __('emails.admin_loggedin_email') }}** {{ $userEmail }}
- **{{ __('emails.admin_loggedin_login_time') }}** {{ $loginTime }} GMT+7

{{ __('emails.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
