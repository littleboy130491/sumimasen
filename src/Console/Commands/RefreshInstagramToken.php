<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class RefreshInstagramToken extends Command
{
    protected $signature = 'instagram:refresh-token';
    protected $description = 'Refresh Instagram long-lived access token and update .env file';

    public function handle()
    {
        $accessToken = config('cms.instagram.access_token'); // or env('INSTAGRAM_ACCESS_TOKEN')
        $response = Http::get('https://graph.instagram.com/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $accessToken,
        ]);

        if ($response->successful()) {
            $newToken = $response->json('access_token');
            $expiresIn = $response->json('expires_in');

            // Update .env (you may want to update DB/config instead for more advanced setups)
            $envPath = base_path('.env');
            $envContent = File::get($envPath);
            $envContent = preg_replace(
                '/INSTAGRAM_ACCESS_TOKEN=.*/',
                'INSTAGRAM_ACCESS_TOKEN=' . $newToken,
                $envContent
            );
            File::put($envPath, $envContent);

            $this->info("Token refreshed! Expires in: {$expiresIn} seconds.");
        } else {
            $this->error("Failed to refresh token: " . $response->body());
        }
    }
}