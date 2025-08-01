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
        // Filament uses the 'web' guard,
        $isAdminLogin = ($event->guard === 'web') && $event->user instanceof User;

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
