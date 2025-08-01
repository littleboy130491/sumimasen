<?php

namespace Littleboy130491\Sumimasen\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Littleboy130491\Sumimasen\Mail\AdminLoggedInNotification;

class SendAdminLoginNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // Check if the user is an instance of App\Models\User and this is an admin login
        // Filament typically uses the 'web' guard, but we also check for 'filament' guard
        // We also check if the request URL contains admin path to ensure it's an admin login
        $isAdminLogin = ($event->guard === 'web' || $event->guard === 'filament') &&
                       $event->user instanceof User &&
                       (Request::is('admin') || Request::is('admin/*') || str_contains(Request::url(), '/admin'));

        if ($isAdminLogin) {
            // Get the first admin user as the recipient (or use config)
            $adminUser = User::first();
            $adminEmail = $adminUser ? $adminUser->email : config('cms.site_email');

            if ($adminEmail) {
                // Get IP address and site URL for the notification
                $ipAddress = Request::ip();
                $siteUrl = config('app.url');
                
                Mail::to($adminEmail)->send(new AdminLoggedInNotification($event->user, $ipAddress, $siteUrl));
            }
        }
    }
}
