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
        return [
            Action::make('view')
                ->label('View')
                ->url(fn() => $this->resolvePublicUrl(), shouldOpenInNewTab: true)
                ->color('gray')
                ->visible(fn() => filled($this->resolvePublicUrl())),
            $this->getSaveFormAction()
                ->formId('form'),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function resolvePublicUrl(): ?string
    {
        $record = $this->getRecord()->refresh();
        $slug = $record->slug;

        $contentModels = config('cms.content_models');

        // find the entry whose slug (or key) matches and is a content model with archive
        $meta = collect($contentModels)
            ->first(
                fn(array $meta, string $key) => ($meta['slug'] ?? $key) === $slug &&
                ($meta['type'] ?? null) === 'content'
            );

        if (!$meta || !($meta['has_archive'] ?? false)) {
            return null;
        }

        return route('cms.page', [
            app()->getLocale(),
            $slug,
        ]);
    }
}
