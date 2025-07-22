<?php

namespace Littleboy130491\Sumimasen\Models;

use Datlechin\FilamentMenuBuilder\Models\MenuItem as BaseMenuItem;
use Illuminate\Database\Eloquent\Casts\Attribute;

class MenuItem extends BaseMenuItem
{
    /**
     * Override the title attribute to handle translations
     */
    protected function title(): Attribute
    {
        return Attribute::get(function (string $value) {
            return $this->translateTitle($value);
        });
    }

    /**
     * Get the raw title without translation (useful for editing)
     */
    public function getRawTitleAttribute(): string
    {
        return $this->attributes['title'];
    }

    /**
     * Translate title - checks menu-specific translations first
     */
    private function translateTitle(string $title): string
    {

        // First, check if title contains translation variables like {{menu.tentang}}
        if (preg_match_all('/\{\{([^}]+)\}\}/', $title, $matches)) {
            $processedTitle = $title;

            foreach ($matches[1] as $translationKey) {
                $translatedValue = __($translationKey);

                // Only replace if translation exists
                if ($translatedValue !== $translationKey) {
                    $processedTitle = str_replace('{{' . $translationKey . '}}', $translatedValue, $processedTitle);
                }
            }

            return $processedTitle;
        }

        // Normalize title to a valid translation key
        $normalizedKey = $this->normalizeTranslationKey($title);

        // Try menu-specific translations first (scoped to menu only)
        $fullKey = 'menu.' . $normalizedKey;

        $menuTranslated = __($fullKey);

        if ($menuTranslated !== $fullKey) {
            return $menuTranslated;
        }

        // Return original title if no menu translation found
        return $title;
    }

    /**
     * Normalize title to a valid translation key
     */
    private function normalizeTranslationKey(string $title): string
    {
        // Clean up the title to make a readable key
        $key = strtolower(trim($title));

        // Replace common patterns
        $replacements = [
            ' & ' => '_and_',
            '&' => '_and_',
            ' ' => '_',
            '-' => '_',
            '/' => '_or_',
            '!' => '',
            '?' => '',
            '.' => '',
            ',' => '',
        ];

        $key = str_replace(array_keys($replacements), array_values($replacements), $key);

        // Remove any remaining special characters and clean up multiple underscores
        $key = preg_replace('/[^a-z0-9_]/', '', $key);

        $key = preg_replace('/_+/', '_', $key); // Replace multiple underscores with single

        $finalKey = trim($key, '_'); // Remove leading/trailing underscores

        return $finalKey;
    }
}