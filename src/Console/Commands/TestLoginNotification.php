<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Littleboy130491\Sumimasen\Mail\AdminLoggedInNotification;
use App\Models\User;

class TestLoginNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-login-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a test admin login notification email.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $testUser = User::first();

        if (! $testUser) {
            $this->error('No users found in the database to send a test notification for.');

            return 1;
        }

        $recipientEmail = config('cms.site_email');

        if (! $recipientEmail) {
            $this->error('The CMS site email (CMS_SITE_EMAIL in .env) is not configured.');

            return 1;
        }

        try {
            Mail::to($recipientEmail)->send(new AdminLoggedInNotification($testUser));
            $this->info("Test login notification sent to {$recipientEmail} for user: {$testUser->email}");
        } catch (\Exception $e) {
            $this->error('Failed to send test notification: '.$e->getMessage());
            logger()->error('TestLoginNotification Error: '.$e->getMessage(), ['exception' => $e]);

            return 1;
        }

        return 0;
    }
}
