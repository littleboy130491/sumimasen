<?php

namespace Littleboy130491\Sumimasen\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Littleboy130491\Sumimasen\Models\Page;

class PageExporter extends Exporter
{
    protected static ?string $model = Page::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['featuredImage', 'author', 'parent']);
    }

    /**
     * Get translatable attributes - these are the JSON columns in your database
     */
    protected static function getTranslatableAttributes(): array
    {
        return ['title', 'slug', 'content', 'excerpt', 'section'];
    }

    /**
     * Get available locales from your database data
     */
    protected static function getAvailableLocales(): array
    {
        return ['id', 'en', 'zh-cn', 'ko'];
    }

    /**
     * Override resolveRecord to add virtual attributes for translations
     */
    public static function resolveRecord(Model $baseRecord): array
    {
        /** @var Page $record */
        $record = $baseRecord;

        // Start with the base array
        $data = $record->toArray();

        // Get translatable attributes and available locales
        $translatableAttributes = static::getTranslatableAttributes();
        $availableLocales = static::getAvailableLocales();

        // Add translation data as separate keys
        foreach ($translatableAttributes as $attribute) {
            // Get the JSON data for this attribute
            $translations = $record->getAttribute($attribute);

            // Handle case where translations might be null or not an array
            if (! is_array($translations)) {
                $translations = [];
            }

            foreach ($availableLocales as $locale) {
                $key = "{$attribute}_{$locale}";
                $value = $translations[$locale] ?? '';

                // Handle arrays (like section data) - convert to JSON string
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                $data[$key] = $value;
            }
        }

        // Add related data
        $data['author_name'] = $record->author?->name ?? '';
        $data['parent_title'] = $record->parent?->title ?? '';

        // Handle featured_image - if it's a relationship, get the ID or path
        if ($record->featuredImage) {
            $data['featured_image_path'] = $record->featuredImage->path ?? $record->featuredImage->url ?? '';
        } else {
            $data['featured_image_path'] = '';
        }

        return $data;
    }

    public static function getColumns(): array
    {
        $columns = [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state instanceof \UnitEnum ? $state->value : $state),
            ExportColumn::make('template')->label('Template'),
            ExportColumn::make('menu_order')->label('Menu Order'),
            ExportColumn::make('parent_id')->label('Parent ID'),
            ExportColumn::make('author_id')->label('Author ID'),
            ExportColumn::make('author_name')->label('Author Name'),
            ExportColumn::make('parent_title')->label('Parent Page Title'),
            ExportColumn::make('featured_image')->label('Featured Image ID'),
            ExportColumn::make('featured_image_path')->label('Featured Image Path'),
            ExportColumn::make('published_at')->label('Published At'),
            ExportColumn::make('created_at')->label('Created At'),
            ExportColumn::make('updated_at')->label('Updated At'),
            ExportColumn::make('deleted_at')->label('Deleted At'),
            ExportColumn::make('custom_fields')
                ->label('Custom Fields (JSON)')
                ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_UNESCAPED_UNICODE) : $state),
        ];

        // Get translatable attributes and available locales
        $translatableAttributes = static::getTranslatableAttributes();
        $availableLocales = static::getAvailableLocales();

        // Add columns for each translation
        foreach ($translatableAttributes as $attribute) {
            foreach ($availableLocales as $locale) {
                $columns[] = ExportColumn::make("{$attribute}_{$locale}")
                    ->label(ucfirst($attribute).' ('.strtoupper($locale).')')
                    ->formatStateUsing(function ($state) {
                        // If it's already a string, return as is
                        if (is_string($state)) {
                            return $state;
                        }
                        // If it's an array, convert to JSON
                        if (is_array($state)) {
                            return json_encode($state, JSON_UNESCAPED_UNICODE);
                        }

                        return $state ?? '';
                    });
            }
        }

        return $columns;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your Page export has completed and '.number_format($export->successful_rows).' '.Str::plural('row', $export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.Str::plural('row', $failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
