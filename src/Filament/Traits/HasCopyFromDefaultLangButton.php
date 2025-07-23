<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

trait HasCopyFromDefaultLangButton
{

    /**
     * @return Action
     */
    protected static function copyFromDefaultLangAction(): Action
    {
        return Action::make('copyFromDefaultLang')
            ->label('Copy from Default Language')
            ->icon('heroicon-m-language')
            ->color('gray')
            ->action(function (Get $get, Set $set, $livewire, string $locale) {
                static::copySectionsFromDefaultLanguage($locale, $livewire, $set);
            });
    }

    /**
     * Copy sections from default language to target locale
     */
    protected static function copySectionsFromDefaultLanguage(string $targetLocale, \Livewire\Component $livewire, ?\Filament\Forms\Set $set = null): void
    {
        $defaultLocale = config('cms.default_language', 'en');

        // Don't copy if target locale is the same as default locale
        if ($targetLocale === $defaultLocale) {
            Notification::make()
                ->title('Cannot copy to default language')
                ->warning()
                ->send();
            return;
        }

        try {
            // Get the SPECIFIC record with safety checks
            $record = null;

            if (method_exists($livewire, 'getRecord')) {
                $record = $livewire->getRecord();
            } elseif (property_exists($livewire, 'record')) {
                $record = $livewire->record;
            }

            if (!$record) {
                Notification::make()
                    ->title('No record found')
                    ->danger()
                    ->send();
                return;
            }

            // Get sections from the default locale FOR THIS SPECIFIC RECORD
            $defaultSections = $record->getTranslation('section', $defaultLocale, false) ?? [];

            if (empty($defaultSections)) {
                Notification::make()
                    ->title('No default content found')
                    ->warning()
                    ->send();
                return;
            }

            $processedSections = static::processCuratorFields($defaultSections);

            // Only update the form field if Set callback is provided
            // Do NOT directly modify the record's translation
            if ($set) {
                $set('section.' . $targetLocale, $processedSections);
            }

            Notification::make()
                ->title('Content copied')
                ->body('The content has been copied to the form. Remember to save the form to persist the changes.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            logger('Error copying from default language', [
                'error' => $e->getMessage(),
                'record_id' => $record->id ?? 'unknown',
                'target_locale' => $targetLocale
            ]);

            Notification::make()
                ->title('Copy failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Recursively process data to convert curator field integers to proper structure
     */
    protected static function processCuratorFields($data, int $depth = 0): mixed
    {
        // Prevent infinite recursion
        if ($depth > 20) {
            return $data;
        }

        // Handle arrays
        if (is_array($data)) {
            $processed = [];
            foreach ($data as $key => $value) {
                $processed[$key] = static::processCuratorFields($value, $depth + 1);
            }
            return $processed;
        }

        // Handle positive integers that might be media IDs
        // Convert them to UUID-keyed array structure expected by curator picker
        if (is_int($data) && $data > 0) {
            try {
                $media = \Awcodes\Curator\Models\Media::find($data);
                if ($media) {
                    // Create UUID-keyed structure like curator picker expects
                    $uuid = (string) \Illuminate\Support\Str::uuid();
                    return [
                        $uuid => [
                            'id' => $media->id,
                            'disk' => $media->disk,
                            'directory' => $media->directory,
                            'visibility' => $media->visibility,
                            'name' => $media->name,
                            'path' => $media->path,
                            'width' => $media->width,
                            'height' => $media->height,
                            'size' => $media->size,
                            'type' => $media->type,
                            'ext' => $media->ext,
                            'alt' => $media->alt,
                            'title' => $media->title,
                            'description' => $media->description,
                            'caption' => $media->caption,
                            'exif' => $media->exif,
                            'curations' => $media->curations,
                            'created_at' => $media->created_at,
                            'updated_at' => $media->updated_at,
                            'tenant_id' => $media->tenant_id,
                            'url' => $media->url,
                            'thumbnail_url' => $media->thumbnail_url,
                            'medium_url' => $media->medium_url,
                            'large_url' => $media->large_url,
                            'resizable' => $media->resizable,
                            'size_for_humans' => $media->size_for_humans,
                            'pretty_name' => $media->pretty_name,
                        ]
                    ];
                }
            } catch (\Exception $e) {
                // If error occurs, return original value
                return $data;
            }
        }

        // Return other data types as-is
        return $data;
    }
}