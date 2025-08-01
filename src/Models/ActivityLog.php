<?php

namespace Littleboy130491\Sumimasen\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ActivityLog extends Model
{
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

    public function newQuery(): Builder
    {
        return parent::newQuery()->whereRaw('1=0'); // Always return empty query
    }

    public static function all($columns = ['*']): Collection
    {
        return static::getRows();
    }

    public static function getRows(): Collection
    {
        $logPath = storage_path('logs/activity.log');
        
        if (!file_exists($logPath)) {
            return collect();
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

                    $attributes = array_merge([
                        'id' => md5($line),
                        'timestamp' => $timestamp,
                        'activity' => $message,
                        'raw_line' => $line,
                    ], $context);

                    return new static($attributes);
                }

                return null;
            })
            ->filter()
            ->reverse()
            ->values();
    }

    // Disable database operations
    public function save(array $options = [])
    {
        return true;
    }

    public function delete()
    {
        return true;
    }
}