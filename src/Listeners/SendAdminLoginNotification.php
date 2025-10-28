<?php

namespace Littleboy130491\Sumimasen\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Littleboy130491\Sumimasen\Mail\AdminLoggedInNotification;
use Littleboy130491\Sumimasen\Services\ActivityLogger;

class SendAdminLoginNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected ActivityLogger $activityLogger;

    /**
     * Create the event listener.
     */
    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // Log all login activities
        $this->activityLogger->logLogin();

        // Check if the user is an instance of App\Models\User and this is an admin login
        // Filament uses the 'web' guard,
        $isAdminLogin = ($event->guard === 'web') && $event->user instanceof User;

        if ($isAdminLogin) {
            $adminEmail = config('cms.site_email') ?? '';

            if ($adminEmail) {
                // Get IP address and site URL for the notification
                $ipAddress = Request::ip();
                $siteUrl = config('app.url');

                Mail::to($adminEmail)->send(new AdminLoggedInNotification($event->user, $ipAddress, $siteUrl));
            }
        }
    }
}
