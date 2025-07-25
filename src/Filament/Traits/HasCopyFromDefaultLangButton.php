<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

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
            ->visible(fn ($arguments) => $arguments['locale'] !== config('cms.default_language'))
            ->action(function (Get $get, Set $set, $livewire, $arguments) {
                $locale = $arguments['locale'] ?? null;
                if (! $locale) {
                    return;
                }

                // Build list of base translatable fields
                $translatableFields = $livewire->record->translatable;

                static::copyAllTranslatableFieldsFromDefaultLanguage(
                    locale: $locale,
                    livewire: $livewire,
                    get: $get,
                    set: $set,
                    translatableFields: $translatableFields,
                );
            })
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Copy content?')
            ->modalDescription('This will overwrite the current locale\'s fields with content from the default language.')
            ->modalIconColor('warning');
    }

    /* -----------------------------------------------------------------
     |  Internal helpers
     | -----------------------------------------------------------------
     */

    /**
     * Copies values for the given base fields from the default locale
     * into the target locale and pushes them into the form state.
     */
    protected static function copyAllTranslatableFieldsFromDefaultLanguage(
        string $locale,
        $livewire,
        ?Get $get,
        ?Set $set,
        array $translatableFields,
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

        if (! $record) {
            Notification::make()->title('No record found')->danger()->send();

            return;
        }

        $copied = false;

        foreach ($translatableFields as $field) {
            if (! static::modelHasColumn($field)) {
                continue;
            }

            if ($set) {
                // Get the value from the default language
                $defaultLangFieldValue = $get("{$field}.{$default}");

                if (empty($defaultLangFieldValue)) {
                    // Skip if the default language field is empty
                    continue;
                }
                // Set the value in the target locale
                $set("{$field}.{$locale}", $defaultLangFieldValue);
            }

            $copied = true;
        }

        $copied
            ? Notification::make()
                ->title('All fields copied successfully')
                ->body('Content has been copied from default language. Save the form to apply the changes.')
                ->success()
                ->send()
            : Notification::make()
                ->title('No default content found')
                ->warning()
                ->send();
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
