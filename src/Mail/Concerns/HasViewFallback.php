<?php

namespace Littleboy130491\Sumimasen\Mail\Concerns;

use Illuminate\Support\Facades\View;

trait HasViewFallback
{
    /**
     * Get the view name with fallback logic.
     * First tries the application view, then falls back to package view.
     */
    protected function getViewWithFallback(string $view): string
    {
        // First try the application view (user's custom template)
        if (View::exists($view)) {
            return $view;
        }

        // Fallback to the package view
        return 'sumimasen-cms::' . $view;
    }
}