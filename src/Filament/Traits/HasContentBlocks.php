<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Builder as FormsBuilder;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms\Components\TextInput;

trait HasContentBlocks
{
    private static function getCompleteBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('complete')
            ->schema([
                TextInput::make('heading'),
                TextInput::make('group'),
                TiptapEditor::make('description')->columnSpan('full'),
                TextInput::make('cta-label')->label('CTA label'),
                TextInput::make('cta-url')->label('CTA URL'),
                CuratorPicker::make('media_id')
                    ->label('Media')
                    ->helperText('Accepted file types: image or document'),
            ])
            ->columns(2);
    }

    private static function getSimpleBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('simple')
            ->schema([
                TextInput::make('heading'),
                TiptapEditor::make('description'),
            ])
            ->columns(1);
    }

    private static function getVideoBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('video')
            ->schema([
                TextInput::make('heading'),
                TextInput::make('group'),
                TiptapEditor::make('description')->columnSpan('full'),
                TextInput::make('video_url'),
            ])
            ->columns(2);
    }

    protected static function getContentBlocks(): array
    {
        return [
            static::getCompleteBlock(),
            static::getSimpleBlock(),
            static::getVideoBlock(),
        ];
    }
}
