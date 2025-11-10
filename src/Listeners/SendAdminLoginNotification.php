<?php

namespace Littleboy130491\Sumimasen\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
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
        try {
            // Log all login activities
            $this->activityLogger->logLogin();

            // Check if the user is an instance of App\Models\User and this is an admin login
            // Filament uses the 'web' guard,
            $isAdminLogin = ($event->guard === 'web') &&
                           $event->user instanceof User &&
                           $event->user->hasRole(['admin', 'super_admin', 'super-admin']);

            if ($isAdminLogin) {
                // Capture IP address and site URL before queueing to ensure reliability
                $ipAddress = Request::ip() ?? 'Unknown';
                $siteUrl = config('app.url', '');

                $adminEmails = $this->getAdminEmails();

                if (! empty($adminEmails)) {
                    foreach ($adminEmails as $recipient) {
                        try {
                            Mail::to($recipient)->send(new AdminLoggedInNotification($event->user, $ipAddress, $siteUrl));
                        } catch (\Exception $e) {
                            Log::error('Failed to send admin login notification', [
                                'error' => $e->getMessage(),
                                'recipient' => $recipient,
                                'user_id' => $event->user->id,
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in SendAdminLoginNotification handler', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get admin email addresses with fallback
     */
    protected function getAdminEmails(): array
    {
        $adminEmails = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super_admin', 'super-admin']);
            })
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($adminEmails)) {
            $fallback = config('cms.site_email') ?? '';

            if ($fallback) {
                $adminEmails = [$fallback];
            }
        }

        return $adminEmails;
    }
}
