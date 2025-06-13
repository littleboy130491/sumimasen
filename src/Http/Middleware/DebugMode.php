<?php

namespace Littleboy130491\Sumimasen\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Littleboy130491\Sumimasen\Services\DebugCollector;

class DebugMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $debugConfig = config('cms.debug_mode');

        // Check if debug mode is enabled and environment is allowed
        if (!$debugConfig['enabled'] || !in_array(app()->environment(), $debugConfig['environments'])) {
            return $next($request);
        }

        // Initialize DebugCollector
        app()->singleton('debug.collector', function () {
            return new DebugCollector();
        });

        // Add route data
        if (Route::current()) {
            app('debug.collector')->addRouteData(Route::current());
        }

        $response = $next($request);

        // Inject debug comments into HTML responses
        if ($response instanceof Response && $this->shouldInjectDebugInfo($response)) {
            $this->injectDebugComments($response);
        }

        return $response;
    }

    /**
     * Determine if debug information should be injected into the response.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return bool
     */
    protected function shouldInjectDebugInfo(Response $response): bool
    {
        return $response->headers->get('Content-Type', '') === 'text/html; charset=UTF-8' &&
            $response->getStatusCode() === 200 &&
            !request()->ajax(); // Do not inject for AJAX requests
    }

    /**
     * Inject debug comments into the response content.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    protected function injectDebugComments(Response $response): void
    {
        $content = $response->getContent();
        $debugInfo = app('debug.collector')->getData();

        $debugComment = $this->generateDebugComment($debugInfo);

        // Inject before the closing </body> tag or after <head>
        if (Str::contains($content, '</body>')) {
            $content = Str::replaceLast('</body>', $debugComment . "\n</body>", $content);
        } elseif (Str::contains($content, '</body>')) {
            $content = Str::replaceLast('</body>', $debugComment . "\n</body>", $content);
        } elseif (Str::contains($content, '</head>')) {
            $content = Str::replaceLast('</head>', $debugComment . "\n</head>", $content);
        } elseif (Str::contains($content, '</head>')) {
            $content = Str::replaceLast('</head>', $debugComment . "\n</head>", $content);
        } else {
            // Fallback: prepend to content if no suitable tag found
            $content = $debugComment . "\n" . $content;
        }

        $response->setContent($content);
    }

    /**
     * Generate the HTML debug comment.
     *
     * @param  array  $debugInfo
     * @return string
     */
    protected function generateDebugComment(array $debugInfo): string
    {
        $comment = "\n<!-- DEBUG MODE ACTIVE -->\n";
        $comment .= "<!-- Request ID: " . (string) Str::uuid() . " -->\n";
        $comment .= "<!-- Timestamp: " . now()->toISOString() . " -->\n";
        $comment .= "<!-- Environment: " . app()->environment() . " -->\n";

        // Route information
        if (isset($debugInfo['route'])) {
            $comment .= "<!-- ROUTE: " . json_encode($debugInfo['route'], JSON_PRETTY_PRINT) . " -->\n";
        }

        // View information
        if (isset($debugInfo['views'])) {
            foreach ($debugInfo['views'] as $view) {
                $comment .= "<!-- VIEW: " . $view['template'] . " -->\n";
                $comment .= "<!-- VIEW VARIABLES: " . json_encode($view['variables'], JSON_PRETTY_PRINT) . " -->\n";
            }
        }

        // Cache information
        if (isset($debugInfo['cache'])) {
            $comment .= "<!-- CACHE: " . json_encode($debugInfo['cache'], JSON_PRETTY_PRINT) . " -->\n";
        }

        // Query information
        if (isset($debugInfo['queries']) && config('cms.debug_mode.include_queries')) {
            $comment .= "<!-- DATABASE QUERIES: " . json_encode($debugInfo['queries'], JSON_PRETTY_PRINT) . " -->\n";
        }

        // Component information
        if (isset($debugInfo['components'])) {
            foreach ($debugInfo['components'] as $component) {
                $comment .= "<!-- COMPONENT: " . $component['name'] . " -->\n";
                $comment .= "<!-- COMPONENT DATA: " . json_encode($component['data'], JSON_PRETTY_PRINT) . " -->\n";
            }
        }

        // Performance metrics
        $comment .= "<!-- MEMORY USAGE: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB -->\n";
        $comment .= "<!-- EXECUTION TIME: " . round((microtime(true) - LARAVEL_START), 4) . " seconds -->\n";

        $comment .= "<!-- END DEBUG MODE -->\n";

        return $comment;
    }
}