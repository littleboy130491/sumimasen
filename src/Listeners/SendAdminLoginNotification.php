<?php

namespace Littleboy130491\Sumimasen\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
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
        // Check if the login guard is 'filament' and the user is an instance of App\Models\User
        if ($event->guard === 'filament' && $event->user instanceof User) {
            // $adminEmail = config('cms.site_email'); // Uses the email from config/cms.php
            $adminUser = User::first();

            if (!$adminUser) {
                $this->error('No users found in the database to send a test notification for.');

                return 1;
            }

            $adminEmail = $adminUser->email;

            if ($adminEmail) {
                Mail::to($adminEmail)->send(new AdminLoggedInNotification($event->user));
            }
        }
    }
}
