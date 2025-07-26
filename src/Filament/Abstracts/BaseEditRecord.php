<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Filament\Actions;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Littleboy130491\Sumimasen\Enums\ContentStatus;

abstract class BaseEditRecord extends EditRecord
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('View')
                ->url(fn () => $this->resolvePublicUrl(), shouldOpenInNewTab: true)
                ->color('gray')
                ->visible(fn () => filled($this->resolvePublicUrl())),
            $this->getSaveFormAction()
                ->formId('form'),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Build the public-facing URL for the current record.
     * Returns null when the model is not registered or has no public route.
     * Appends preview=true if content status is not published.
     */
    protected function resolvePublicUrl(): ?string
    {
        $modelClass = $this->getModel();

        $meta = collect(config('cms.content_models'))
            ->first(fn (array $meta, string $key) => $meta['model'] === $modelClass);

        if (! $meta) {
            return null;
        }

        $configKey = array_search($meta, config('cms.content_models'), true);
        $key = $meta['slug'] ?? $configKey;
        $type = $meta['type'] ?? null;

        // Get fresh record data
        $record = $this->getRecord()->refresh();
        $slug = $record->slug ?: $record->getTranslation('slug', config('cms.default_language', false));

        if (! $slug) {
            return null;
        }

        $url = match ($type) {
            'taxonomy' => $meta['has_archive'] ?? false
            ? route('cms.taxonomy.archive', [app()->getLocale(), $key, $slug])
            : null,
            'content' => $meta['has_single'] ?? false
            ? route('cms.single.content', [app()->getLocale(), $key, $slug])
            : null,
            default => null,
        };

        // For static pages, use the static page route
        if ($type === 'content' && $configKey === config('cms.static_page_slug')) {
            $url = route('cms.static.page', [app()->getLocale(), $slug]);
        }

        // Append preview=true if content has status and is not published
        if ($url && isset($record->status) && $record->status !== ContentStatus::Published) {
            $url .= '?preview=true';
        }

        return $url;
    }
}
