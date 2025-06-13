<?php

namespace Littleboy130491\Sumimasen\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('lang');
        $defaultLanguage = Config::get('cms.default_language', 'en');

        // Validate against supported locales (optional but recommended)
        if (array_key_exists($locale, Config::get('cms.language_available', ['en' => 'English', 'id' => 'Indonesian']))) {
            App::setLocale($locale);
        } else {
            App::setLocale($defaultLanguage);
        }

        // Set fallback locale for Spatie Translatable
        App::setFallbackLocale($defaultLanguage);

        return $next($request);
    }
}