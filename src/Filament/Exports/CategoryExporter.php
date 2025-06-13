<?php

namespace Littleboy130491\Sumimasen\Filament\Exports;

use Littleboy130491\Sumimasen\Models\Category;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CategoryExporter extends Exporter
{
    protected static ?string $model = Littleboy130491\Sumimasen\Models\Category::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('title'),
            ExportColumn::make('slug'),
            ExportColumn::make('content'),
            ExportColumn::make('featured_image'),
            ExportColumn::make('menu_order'),
            ExportColumn::make('template'),
            ExportColumn::make('posts')->formatStateUsing(fn($state) => $state->pluck('id')->join(', ')), // Related Post IDs
            ExportColumn::make('parent.id'), // Related Category ID
            ExportColumn::make('children')->formatStateUsing(fn($state) => $state->pluck('id')->join(', ')), // Related Category IDs
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your Category export has completed and ' . number_format($export->successful_rows) . ' ' . Str::plural('row', $export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . Str::plural('row', $failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}