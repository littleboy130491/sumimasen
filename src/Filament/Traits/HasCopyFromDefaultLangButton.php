<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Field;

trait HasCopyFromDefaultLangButton
{

    /**
     * Attach this to Translate fields from SolutionForest actions method.
     *
     * Example usage:
     * 
     * Translate::make()
     *     ->columns(2)
     *     ->schema(function (string $locale): array {
     *         return static::topLeftSchema($locale);
     *     })
     *     ->contained(false)
     *     ->actions([
     *         static::copyFromDefaultLangAction(), // Add this line to include the copy action
     *     ]);
     *
     * @return Action
     */
    protected static function copyFromDefaultLangAction(): Action
    {
        return Action::make('copyFromDefaultLang')
            ->label('Copy Content from Default Language')
            ->icon('heroicon-m-language')
            ->color('gray')
            ->visible(fn($arguments) => $arguments['locale'] !== config('cms.default_language'))
            ->action(function (Get $get, Set $set, $livewire, $arguments) {
                $locale = $arguments['locale'] ?? null;
                if (!$locale) {
                    return;
                }

                $locales = collect(array_keys(config('cms.language_available')));
                $pattern = '/^(.+)\.(' . $locales->join('|') . ')$/';

                $translatableFields = collect($livewire->getForm('form')->getFlatComponents())
                    ->filter(fn($c) => $c instanceof Field)
                    ->filter(fn($c) => preg_match($pattern, $c->getName()))
                    ->map->getName()
                    ->values()
                    ->all();

                $baseFieldsNames = collect($translatableFields)
                    ->map(fn($name) => explode('.', $name)[0])
                    ->unique()
                    ->values()
                    ->all();

                static::copyAllTranslatableFieldsFromDefaultLanguage(
                    $locale,
                    $livewire,
                    $set,
                    $baseFieldsNames
                );
            })
            ->link();
    }
    protected static function copyAllTranslatableFieldsFromDefaultLanguage(string $targetLocale, \Livewire\Component $livewire, ?Set $set = null, array $translatableFields): void
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
            // Get the record
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


            $copiedData = [];
            $hasContent = false;

            foreach ($translatableFields as $field) {
                if (static::modelHasColumn($field)) {
                    $defaultValue = $record->getTranslation($field, $defaultLocale, false);
                    if ($defaultValue !== null && $defaultValue !== '') {
                        $processedValue = $field === 'section'
                            ? static::processCuratorFields($defaultValue)
                            : $defaultValue;

                        $copiedData[$field] = $processedValue;
                        $hasContent = true;
                    }
                }
            }

            if (!$hasContent) {
                Notification::make()
                    ->title('No default content found')
                    ->warning()
                    ->send();

                return;
            }

            // Update form fields
            if ($set) {
                foreach ($copiedData as $field => $value) {
                    $set($field . '.' . $targetLocale, $value);
                }
            }

            Notification::make()
                ->title('All fields copied successfully')
                ->body('Content has been copied from default language. Remember to save the form.')
                ->success()
                ->send();

        } catch (\Exception $e) {
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
                        ],
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

    // Check if the model has a specific column
    protected static function modelHasColumn(string $column): bool
    {
        $modelClass = app(static::$model);

        return in_array($column, $modelClass->getFillable()) ||
            array_key_exists($column, $modelClass->getCasts()) ||
            $modelClass->hasAttribute($column);
    }

}