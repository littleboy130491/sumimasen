<?php

namespace Littleboy130491\Sumimasen\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;
use Littleboy130491\Sumimasen\Models\Submission;

class SubmissionExporter extends Exporter
{
    protected static ?string $model = Submission::class;

    public static function getColumns(): array
    {
        // Get all unique field keys from existing submissions
        $allFieldKeys = collect();

        // Get a sample submission to determine field structure
        $sampleSubmission = Submission::first();
        if ($sampleSubmission && $sampleSubmission->fields) {
            $allFieldKeys = collect(array_keys($sampleSubmission->fields));
        } else {
            // Fallback to default structure if no submissions exist
            $allFieldKeys = collect(['name', 'phone', 'email', 'message']);
        }

        // Get all unique keys from all submissions to ensure we don't miss any fields
        $allSubmissions = Submission::whereNotNull('fields')->get();
        foreach ($allSubmissions as $submission) {
            if ($submission->fields) {
                $allFieldKeys = $allFieldKeys->merge(array_keys($submission->fields));
            }
        }

        $allFieldKeys = $allFieldKeys->unique()->sort();

        $columns = [
            ExportColumn::make('id'),
        ];

        // Dynamically create columns for each field
        foreach ($allFieldKeys as $fieldKey) {
            $columns[] = ExportColumn::make("fields.{$fieldKey}")
                ->label(Str::title(str_replace('_', ' ', $fieldKey)));
        }

        // Add timestamp columns
        $columns[] = ExportColumn::make('created_at');
        $columns[] = ExportColumn::make('updated_at');

        return $columns;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your Submission export has completed and '.number_format($export->successful_rows).' '.Str::plural('row', $export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.Str::plural('row', $failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
