<?php

namespace Littleboy130491\Sumimasen\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public function __construct()
    {
        //
    }

    public function log(string $activity, array $data = [], $subject = null): void
    {
        $request = request();

        $logData = [
            'timestamp' => now()->toISOString(),
            'activity' => $activity,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? 'Guest',
            'user_email' => Auth::user()?->email ?? 'N/A',
            'ip_address' => $request?->ip() ?? 'Unknown',
            'user_agent' => $request?->userAgent() ?? 'Unknown',
            'device_type' => $this->getDeviceType($request),
            'platform' => $this->getPlatform($request),
            'browser' => $this->getBrowser($request),
            'session_id' => session()?->getId() ?? 'None',
            'url' => $request?->fullUrl() ?? 'Unknown',
            'method' => $request?->method() ?? 'Unknown',
        ];

        if ($subject) {
            $logData['subject_type'] = get_class($subject);
            $logData['subject_id'] = $subject->id ?? null;
        }

        if (! empty($data)) {
            $logData['additional_data'] = $data;
        }

        Log::channel('activity')->info($activity, $logData);
    }

    public function logLogin(): void
    {
        $this->log('user_logged_in', [
            'guard' => Auth::getDefaultDriver(),
            'login_time' => now()->toDateTimeString(),
        ]);
    }

    public function logCreate($model, array $attributes = []): void
    {
        $this->log('resource_created', [
            'attributes' => $attributes,
            'model_class' => get_class($model),
        ], $model);
    }

    public function logUpdate($model, array $changes = []): void
    {
        $this->log('resource_updated', [
            'changes' => $changes,
            'model_class' => get_class($model),
        ], $model);
    }

    public function logDelete($model): void
    {
        $this->log('resource_deleted', [
            'model_class' => get_class($model),
            'deleted_at' => now()->toDateTimeString(),
        ], $model);
    }

    protected function getDeviceType(): string
    {
        $userAgent = request()->userAgent();

        if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    protected function getPlatform(): string
    {
        $userAgent = request()->userAgent();

        if (preg_match('/windows/i', $userAgent)) {
            return 'Windows';
        }

        if (preg_match('/macintosh|mac os x/i', $userAgent)) {
            return 'macOS';
        }

        if (preg_match('/linux/i', $userAgent)) {
            return 'Linux';
        }

        if (preg_match('/android/i', $userAgent)) {
            return 'Android';
        }

        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'iOS';
        }

        return 'Unknown';
    }

    protected function getBrowser(): string
    {
        $userAgent = request()->userAgent();

        if (preg_match('/chrome/i', $userAgent)) {
            return 'Chrome';
        }

        if (preg_match('/firefox/i', $userAgent)) {
            return 'Firefox';
        }

        if (preg_match('/safari/i', $userAgent)) {
            return 'Safari';
        }

        if (preg_match('/edge/i', $userAgent)) {
            return 'Edge';
        }

        return 'Unknown';
    }
}
