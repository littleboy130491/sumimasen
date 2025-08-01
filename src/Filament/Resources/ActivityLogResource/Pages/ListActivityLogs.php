<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\ActivityLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Littleboy130491\Sumimasen\Filament\Resources\ActivityLogResource;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getTableQuery(): Builder
    {
        // Create a fake query builder since we're reading from files
        return new class extends Builder {
            public function __construct()
            {
                // Empty constructor
            }

            public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
            {
                $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
                $logs = $this->getActivityLogs();
                
                $total = $logs->count();
                $items = $logs->forPage($page, $perPage);

                return new LengthAwarePaginator(
                    $items,
                    $total,
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'pageName' => $pageName,
                    ]
                );
            }

            public function get($columns = ['*'])
            {
                return $this->getActivityLogs();
            }

            private function getActivityLogs(): Collection
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

                            return (object) array_merge([
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
                    ->values();
            }

            // Stub methods to satisfy Builder interface
            public function where($column, $operator = null, $value = null, $boolean = 'and') { return $this; }
            public function orderBy($column, $direction = 'asc') { return $this; }
            public function limit($value) { return $this; }
            public function offset($value) { return $this; }
            public function count() { return $this->getActivityLogs()->count(); }
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            // Add clear logs action if needed
        ];
    }
}