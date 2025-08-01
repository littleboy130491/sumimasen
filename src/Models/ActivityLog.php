<?php

namespace Littleboy130491\Sumimasen\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class ActivityLog extends Model
{
    use Sushi;

    protected $fillable = [
        'timestamp',
        'activity',
        'user_name',
        'user_email',
        'ip_address',
        'device_type',
        'platform',
        'browser',
        'subject_type',
        'subject_id',
        'url',
        'user_agent',
        'session_id',
        'raw_line',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function getRows(): array
    {
        $logPath = storage_path('logs/activity.log');
        
        if (!file_exists($logPath)) {
            return [];
        }

        $logContent = file_get_contents($logPath);
        $lines = explode("\n", $logContent);
        
        return collect($lines)
            ->filter(fn ($line) => !empty(trim($line)))
            ->map(function ($line) {
                // Parse log line format: [timestamp] environment.level: message context
                if (preg_match('/^\[([^\]]+)\] \w+\.\w+: (.+) (\{.+\})$/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $message = $matches[2];
                    $context = json_decode($matches[3], true) ?: [];

                    return array_merge([
                        'id' => md5($line),
                        'timestamp' => $timestamp,
                        'activity' => $message,
                        'raw_line' => $line,
                    ], $context);
                }

                return null;
            })
            ->filter()
            ->reverse()
            ->values()
            ->toArray();
    }
}