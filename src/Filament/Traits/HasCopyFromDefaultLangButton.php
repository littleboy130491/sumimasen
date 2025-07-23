<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Field;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Awcodes\Curator\Models\Media;

trait HasCopyFromDefaultLangButton
{
    /* -----------------------------------------------------------------
     |  Public helper
     | -----------------------------------------------------------------
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

                // Build list of base translatable fields
                $baseFields = static::extractTranslatableBaseFields($livewire);

                static::copyAllTranslatableFieldsFromDefaultLanguage(
                    locale: $locale,
                    livewire: $livewire,
                    set: $set,
                    baseFields: $baseFields,
                );
            })
            ->link();
    }

    /* -----------------------------------------------------------------
     |  Internal helpers
     | -----------------------------------------------------------------
     */

    /**
     * Returns ['title', 'slug', 'content', ...] by inspecting the form.
     */
    protected static function extractTranslatableBaseFields($livewire): array
    {
        $locales = collect(array_keys(config('cms.language_available')));
        $pattern = '/^(.+)\.(' . $locales->join('|') . ')$/';

        return collect($livewire->getForm('form')->getFlatComponents())
            ->filter(fn($c) => $c instanceof Field && preg_match($pattern, $c->getName()))
            ->map(fn($c) => explode('.', $c->getName())[0])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Copies values for the given base fields from the default locale
     * into the target locale and pushes them into the form state.
     */
    protected static function copyAllTranslatableFieldsFromDefaultLanguage(
        string $locale,
        $livewire,
        ?Set $set,
        array $baseFields,
    ): void {
        $default = config('cms.default_language', 'en');

        if ($locale === $default) {
            Notification::make()
                ->title('Cannot copy to default language')
                ->warning()
                ->send();
            return;
        }

        $record = method_exists($livewire, 'getRecord')
            ? $livewire->getRecord()
            : ($livewire->record ?? null);

        if (!$record) {
            Notification::make()->title('No record found')->danger()->send();
            return;
        }

        $copied = false;

        foreach ($baseFields as $field) {
            if (!static::modelHasColumn($field)) {
                continue;
            }

            $value = $record->getTranslation($field, $default, false);

            if (blank($value)) {
                continue;
            }

            $value = $field === 'section'
                ? static::resolveCuratorValue($value)
                : $value;

            if ($set) {
                $set("{$field}.{$locale}", $value);
            }
            $copied = true;
        }

        $copied
            ? Notification::make()
                ->title('All fields copied successfully')
                ->body('Content has been copied from default language. Remember to save the form.')
                ->success()
                ->send()
            : Notification::make()
                ->title('No default content found')
                ->warning()
                ->send();
    }

    /**
     * Recursively process data to convert curator field integers to proper structure
     */
    protected static function resolveCuratorValue($data, int $depth = 0): mixed
    {
        // Prevent infinite recursion
        if ($depth > 20) {
            return $data;
        }

        // Handle arrays
        if (is_array($data)) {
            $processed = [];
            foreach ($data as $key => $value) {
                $processed[$key] = static::resolveCuratorValue($value, $depth + 1);
            }

            return $processed;
        }

        // Handle positive integers that might be media IDs
        // Convert them to UUID-keyed array structure expected by curator picker
        if (is_int($data) && $data > 0) {
            try {
                $media = Media::find($data);
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

    /* -----------------------------------------------------------------
     |  Utility
     | -----------------------------------------------------------------
     */

    protected static function modelHasColumn(string $column): bool
    {
        $model = app(static::$model);

        return in_array($column, $model->getFillable())
            || array_key_exists($column, $model->getCasts())
            || $model->hasAttribute($column);
    }
}