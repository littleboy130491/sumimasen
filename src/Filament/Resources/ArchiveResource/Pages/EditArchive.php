<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\ArchiveResource\Pages;

use Filament\Actions;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Littleboy130491\Sumimasen\Filament\Resources\ArchiveResource;

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

    protected function resolvePublicUrl(): ?string
    {
        $recordSlug = $this->getRecord()->slug;

        $contentModels = config('cms.content_models');

        // find the entry whose slug (or key) matches and is a content model with archive
        $meta = collect($contentModels)
            ->first(
                fn (array $meta, string $key) => ($meta['slug'] ?? $key) === $recordSlug &&
                ($meta['type'] ?? null) === 'content'
            );

        if (! $meta || ! ($meta['has_archive'] ?? false)) {
            return null;
        }

        return route('cms.archive.content', [
            app()->getLocale(),
            $recordSlug,
        ]);
    }
}
