<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

trait HasCopyAllFromDefaultLang
{
    /**
     * Create hint action for copying all translatable fields from default language
     */
    protected static function createCopyAllFromDefaultLangHintAction(string $locale): Action
    {
        return Action::make('copyAllFromDefaultLang')
            ->label('Copy from Default Language')
            ->icon('heroicon-m-language')
            ->color('gray')
            ->action(function (Get $get, Set $set, $livewire) use ($locale) {
                static::copyAllTranslatableFieldsFromDefaultLanguage($locale, $livewire, $set);
            });
    }

    /**
     * Copy all translatable fields from default language to target locale
     */
    protected static function copyAllTranslatableFieldsFromDefaultLanguage(string $targetLocale, \Livewire\Component $livewire, ?Set $set = null): void
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

            // Get all translatable fields from the default locale
            $translatableFields = [
                'title',
                'slug',
                'content',
                'excerpt',
                'section'
            ];

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
}