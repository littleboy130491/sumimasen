@component('mail::message')
# {{ __('sumimasen-cms::emails.admin_loggedin_subject') }}

{{ __('sumimasen-cms::emails.admin_loggedin_body_line1') }}

**{{ __('sumimasen-cms::emails.admin_loggedin_user_details') }}**
- **{{ __('sumimasen-cms::emails.admin_loggedin_name') }}** {{ $userName }}
- **{{ __('sumimasen-cms::emails.admin_loggedin_email') }}** {{ $userEmail }}
- **{{ __('sumimasen-cms::emails.admin_loggedin_login_time') }}** {{ $loginTime }} GMT+7

{{ __('sumimasen-cms::emails.thanks') }}<br>
{{ config('app.name') }}
@endcomponent
