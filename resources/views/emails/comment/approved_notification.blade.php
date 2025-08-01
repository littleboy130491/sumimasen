@component('mail::message')
# Your Comment Has Been Approved

Hello {{ $commentAuthorName }},

Great news! Your comment on "{{ $commentableTitle }}" has been approved and is now visible to other visitors.

## Your Comment:
@component('mail::panel')
    {{ $commentContent }}
@endcomponent

You can view your comment and any replies by visiting the post:

@component('mail::button', ['url' => $commentableUrl])
    View Your Comment
@endcomponent

**Approved on:** {{ $approvedDate }}

Thank you for contributing to our community discussion!

Thanks,<br>
{{ config('app.name') }}
@endcomponent
