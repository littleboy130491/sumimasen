<?php

namespace Littleboy130491\Sumimasen\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PostExporter extends Exporter
{
    protected static ?string $model = null;

    public static function getModel(): string
    {
        return static::$model ??= class_exists(\App\Models\Post::class)
            ? \App\Models\Post::class
            : \Littleboy130491\Sumimasen\Models\Post::class;
    }

    public static function getColumns(): array
    {
        $columns = [
            ExportColumn::make('id'),
            ExportColumn::make('author.name')
                ->label('Author'),
            ExportColumn::make('status')
                ->formatStateUsing(fn($state) => $state?->value ?? $state),
            ExportColumn::make('featured')
                ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),
            ExportColumn::make('menu_order'),
            ExportColumn::make('published_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];

        // Get available languages from CMS config
        $languages = config('cms.language_available', []);
        $translatableFields = ['title', 'slug', 'excerpt', 'content'];

        // Add translatable columns for each language
        foreach ($translatableFields as $field) {
            foreach ($languages as $langCode => $langName) {
                $columns[] = ExportColumn::make("{$field}_{$langCode}")
                    ->label(ucfirst($field) . " ({$langName})")
                    ->formatStateUsing(fn($record) => $record->getTranslation($field, $langCode));
            }
        }

        // Add remaining columns
        $columns = array_merge($columns, [

            // Featured Image
            ExportColumn::make('featuredImage.url')
                ->label('Featured Image'),

            // Relationships
            ExportColumn::make('categories.title')
                ->label('Categories')
                ->formatStateUsing(fn($record) => $record->categories->pluck('title')->join(', ')),
            ExportColumn::make('tags.title')
                ->label('Tags')
                ->formatStateUsing(fn($record) => $record->tags->pluck('title')->join(', ')),

            // Custom fields as JSON
            ExportColumn::make('custom_fields')
                ->listAsJson(),

            // Gallery URLs
            ExportColumn::make('gallery')
                ->label('Gallery URLs')
                ->getStateUsing(function ($record) {
                    $galleryIds = $record->gallery;

                    if (!$galleryIds || !is_array($galleryIds)) {
                        return '';
                    }

                    $urls = \Awcodes\Curator\Models\Media::whereIn('id', $galleryIds)
                        ->get()
                        ->map(fn($media) => $media->url)
                        ->filter()
                        ->toArray();

                    return json_encode($urls);
                }),
        ]);

        return $columns;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your post export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}