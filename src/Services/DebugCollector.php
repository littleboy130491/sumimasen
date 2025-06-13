<?php

namespace Littleboy130491\Sumimasen\Services;

use Illuminate\Support\Str;
use Illuminate\Routing\Route;

class DebugCollector
{
    protected array $data = [];

    public function addViewData(string $view, array $data): void
    {
        $this->data['views'][] = [
            'template' => $view,
            'variables' => $this->sanitizeVariables($data),
            'timestamp' => microtime(true)
        ];
    }

    public function addRouteData(Route $route): void
    {
        $this->data['route'] = [
            'name' => $route->getName(),
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'controller' => $route->getActionName(),
            'middleware' => $route->middleware()
        ];
    }

    public function addCacheData(bool $fromCache, ?string $key = null): void
    {
        $this->data['cache'] = [
            'served_from_cache' => $fromCache,
            'cache_key' => $key,
            'cache_driver' => config('cache.default')
        ];
    }

    public function addQueryData(array $query): void
    {
        $this->data['queries'][] = $query;
    }

    public function addComponentData(string $component, array $data): void
    {
        $this->data['components'][] = [
            'name' => $component,
            'data' => $this->sanitizeVariables($data),
            'timestamp' => microtime(true)
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }

    protected function sanitizeVariables(array $data): array
    {
        $redactedKeys = config('cms.debug_mode.redacted_keys', []);
        $maxArrayItems = config('cms.debug_mode.max_array_items', 50);
        $maxVariableDepth = config('cms.debug_mode.max_variable_depth', 3);

        return $this->sanitizeRecursive($data, $redactedKeys, $maxArrayItems, $maxVariableDepth);
    }

    protected function sanitizeRecursive($value, array $redactedKeys, int $maxArrayItems, int $maxVariableDepth, int $currentDepth = 0)
    {
        if ($currentDepth > $maxVariableDepth) {
            return '[MAX_DEPTH_REACHED]';
        }

        if (is_array($value)) {
            if (count($value) > $maxArrayItems) {
                return ['[LARGE_ARRAY:' . count($value) . '_items]']; // Return as an array
            }
            $sanitized = [];
            foreach ($value as $key => $item) {
                if (in_array($key, $redactedKeys)) {
                    $sanitized[$key] = '[REDACTED]';
                } else {
                    $sanitized[$key] = $this->sanitizeRecursive($item, $redactedKeys, $maxArrayItems, $maxVariableDepth, $currentDepth + 1);
                }
            }
            return $sanitized;
        } elseif (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                // If it can be converted to an array, sanitize its array representation
                return $this->sanitizeRecursive($value->toArray(), $redactedKeys, $maxArrayItems, $maxVariableDepth, $currentDepth + 1);
            }
            return '[OBJECT:' . get_class($value) . ']'; // Return as string for non-array-convertible objects
        } elseif (is_string($value) && Str::length($value) > 200) {
            return '[LONG_STRING:' . Str::length($value) . '_chars]';
        }

        return $value;
    }
}