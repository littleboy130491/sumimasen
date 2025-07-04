<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Builder as FormsBuilder;
use Filament\Forms\Components\TextInput;
use FilamentTiptapEditor\TiptapEditor;

trait HasContentBlocks
{
    private static function getCompleteBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('complete')
            ->schema([
                TextInput::make('heading'),
                TextInput::make('group'),
                TiptapEditor::make('description')
                    ->profile('simple')
                    ->columnSpan('full')
                    ->extraInputAttributes(['style' => 'min-height: 12rem;']),
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
                TiptapEditor::make('description')
                    ->profile('simple')
                    ->columnSpan('full')
                    ->extraInputAttributes(['style' => 'min-height: 12rem;']),
            ])
            ->columns(1);
    }

    private static function getVideoBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('video')
            ->schema([
                TextInput::make('heading'),
                TextInput::make('group'),
                TiptapEditor::make('description')
                    ->profile('simple')
                    ->columnSpan('full')
                    ->extraInputAttributes(['style' => 'min-height: 12rem;']),
                TextInput::make('video_url'),
            ])
            ->columns(2);
    }

    private static function getHotspotBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('hotspot')
            ->schema([
                TextInput::make('heading'),
                TextInput::make('sub-heading'),
                TiptapEditor::make('description')
                    ->profile('simple')
                    ->columnSpan('full')
                    ->extraInputAttributes(['style' => 'min-height: 12rem;']),
                TextInput::make('top')
                    ->numeric(),
                TextInput::make('left')
                    ->numeric(),
                CuratorPicker::make('media_id')
                    ->label('Media')
                    ->helperText('Accepted file types: image or document'),
            ])
            ->columns(2);
    }

    private static function getGalleryBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('gallery')
            ->schema([
                CuratorPicker::make('media_id')
                    ->label('Media')
                    ->multiple()
                    ->acceptedFileTypes(['image/*'])
                    ->helperText('Accepted file types: image'),
            ])
            ->columns(1);
    }

    private static function getImageWithTextBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('image_with_text')
            ->schema([
                CuratorPicker::make('media_id')
                    ->label('Media')
                    ->acceptedFileTypes(['image/*'])
                    ->helperText('Accepted file types: image'),
                TextInput::make('heading'),
            ])
            ->columns(2);
    }

    private static function getCounterBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('counter')
            ->schema([
                TextInput::make('heading'),
                TextInput::make('number')
                    ->numeric()
                    ->label('Counter Number'),
                TextInput::make('prefix')
                    ->label('Counter Prefix'),
                TextInput::make('suffix')
                    ->label('Counter Prefix'),
            ])
            ->columns(2);
    }

    protected static function getContentBlocks(): array
    {
        return [
            static::getCompleteBlock(),
            static::getSimpleBlock(),
            static::getVideoBlock(),
            static::getHotspotBlock(),
            static::getGalleryBlock(),
            static::getImageWithTextBlock(),
            static::getCounterBlock(),
        ];
    }
}
