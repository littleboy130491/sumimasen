<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions\Action;

abstract class BaseEditRecord extends EditRecord
{
    protected function getHeaderActions(): array
    {
        $lang = app()->getLocale();
        $modelClass = $this->getModel();
        $slug = $this->getRecord()->slug;

        return [
            Action::make('view')
                ->label('View Post')
                ->url($this->getRouteUrl($lang, $modelClass, $slug), shouldOpenInNewTab: true)
                ->color('gray')
                ->icon('heroicon-o-eye'),
            $this->getSaveFormAction()
                ->formId('form'),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRouteUrl($lang, $modelClass, $slug)
    {

        $content_model_key = null;
        $content_model = collect(config('cms.content_models'))
            ->first(function ($item, $key) use ($modelClass, &$content_model_key) {
                if ($item['model'] === $modelClass) {
                    $content_model_key = $key; // save the key like 'posts', 'pages', etc.
                    return true;
                }
                return false;
            });
        $type = $content_model['type'] ?? null;

        if ($type === 'taxonomy') {
            return route('cms.taxonomy.archive', [
                $lang,
                $content_model_key,
                $this->getRecord()->slug,
            ]);
        } elseif ($type === 'content') {
            return route('cms.single.content', [
                $lang,
                $content_model_key,
                $this->getRecord()->slug,
            ]);
        }
    }
}
