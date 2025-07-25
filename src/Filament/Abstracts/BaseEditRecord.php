<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Filament\Actions;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\EditRecord;

abstract class BaseEditRecord extends EditRecord
{
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
     * Build the public-facing URL for the current record.
     * Returns null when the model is not registered or has no public route.
     */
    protected function resolvePublicUrl(): ?string
    {
        $modelClass = $this->getModel();

        $meta = collect(config('cms.content_models'))
            ->first(fn (array $meta, string $key) => $meta['model'] === $modelClass);

        if (! $meta) {
            return null;
        }

        $key = $meta['slug'] ?? array_search($meta, config('cms.content_models'), true);
        $type = $meta['type'] ?? null;
        $slug = $this->getRecord()->slug;

        return match ($type) {
            'taxonomy' => $meta['has_archive'] ?? false
            ? route('cms.taxonomy.archive', [app()->getLocale(), $key, $slug])
            : null,
            'content' => $meta['has_archive'] ?? false
            ? route('cms.single.content', [app()->getLocale(), $key, $slug])
            : null,
            default => null,
        };
    }
}
