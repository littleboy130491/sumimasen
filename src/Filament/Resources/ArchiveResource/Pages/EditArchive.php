<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\ArchiveResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Littleboy130491\Sumimasen\Filament\Resources\ArchiveResource;
use Filament\Actions;
use Filament\Pages\Actions\Action;

class EditArchive extends EditRecord
{
    protected static string $resource = ArchiveResource::class;

    protected function getHeaderActions(): array
    {
        $url = $this->resolvePublicUrl();

        return [
            ...(filled($url) ? [
                Action::make('view')
                    ->label('View')
                    ->url($url, shouldOpenInNewTab: true)
                    ->color('gray'),
            ] : []),
            $this->getSaveFormAction()
                ->formId('form'),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Return the front-end archive URL for the current record
     * or null if the model has no archive.
     */
    protected function resolvePublicUrl(): ?string
    {
        $slug = $this->getRecord()->slug;

        // Find the model entry whose slug matches the record slug
        $model = collect(config('cms.content_models'))
            ->first(fn(array $meta) => ($meta['slug'] ?? null) === $slug);

        if (!$model || !($model['has_archive'] ?? false)) {
            return null;
        }

        return route('cms.archive.content', [
            app()->getLocale(),
            $slug,
        ]);
    }

}

