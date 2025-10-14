<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Builder as FormsBuilder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use FilamentTiptapEditor\TiptapEditor;

trait HasContentBlocks
{
    private static function getCompleteBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('complete')
            ->label('Complete Block')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('title'),
                TextInput::make('subtitle'),
                TiptapEditor::make('description')
                    ->profile('simple')
                    ->columnSpan('full')
                    ->extraInputAttributes(['style' => 'min-height: 12rem;']),
                TextInput::make('url'),
                TextInput::make('button_label'),
                TextInput::make('video_url'),
                CuratorPicker::make('media')
                    ->label('Media')
                    ->preserveFilenames()
                    ->helperText('Accepted file types: image or document'),
                CuratorPicker::make('image')
                    ->label('Image')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/*'])
                    ->helperText('Accepted file types: image only'),
                Toggle::make('hide')
                    ->label('Hide Block')
                    ->helperText('Hide this block from display')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getImageWithTextBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('image_with_text')
            ->label('Image with Text')
            ->schema([
                CuratorPicker::make('image')
                    ->label('Image')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/*'])
                    ->helperText('Main image for this block'),
                TextInput::make('heading'),
                TiptapEditor::make('description')
                    ->label('Text Content')
                    ->profile('simple')
                    ->columnSpanFull(),
                Toggle::make('hide')
                    ->label('Hide Block')
                    ->helperText('Hide this block from display')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getSectionWithImageBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('section_with_image')
            ->label('Section with Image')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('title'),
                TextInput::make('subtitle'),
                Textarea::make('description')
                    ->columnSpanFull(),
                CuratorPicker::make('image')
                    ->acceptedFileTypes(['image/*'])
                    ->preserveFilenames(),
                Toggle::make('hide')
                    ->label('Hide Block')
                    ->helperText('Hide this block from display')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getSectionWithLinkBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('section_with_link')
            ->label('Section with Link')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('title'),
                TextInput::make('subtitle'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('url'),
                TextInput::make('button_label'),
                Toggle::make('hide')
                    ->label('Hide Block')
                    ->helperText('Hide this block from display')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getSimpleBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('simple')
            ->label('Simple Block')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('title'),
                TextInput::make('subtitle'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('hide')
                    ->label('Hide Block')
                    ->helperText('Hide this block from display')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getCounterBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('counter')
            ->label('Counter')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('title'),
                TextInput::make('subtitle'),
                TextInput::make('prefix'),
                TextInput::make('suffix'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('hide')
                    ->label('Hide Block')
                    ->helperText('Hide this block from display')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getGalleryBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('gallery')
            ->label('Gallery')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('title'),
                TextInput::make('subtitle'),
                Textarea::make('description')
                    ->columnSpanFull(),
                CuratorPicker::make('gallery')
                    ->acceptedFileTypes(['image/*'])
                    ->multiple()
                    ->preserveFilenames(),
                Toggle::make('hide')
                    ->label('Hide Block')
                    ->helperText('Hide this block from display')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getVideoBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('video')
            ->label('Video')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('title'),
                TextInput::make('subtitle'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('video_url'),
                CuratorPicker::make('image')
                    ->acceptedFileTypes(['image/*'])
                    ->preserveFilenames(),
                Toggle::make('hide')
                    ->label('Hide Block')
                    ->helperText('Hide this block from display')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getHotspotBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('hotspot')
            ->label('Hotspot')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('title'),
                TextInput::make('subtitle'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('left')
                    ->numeric(),
                TextInput::make('top')
                    ->numeric(),
                TextInput::make('pointer'),
                Toggle::make('hide')
                    ->label('Hide Block')
                    ->helperText('Hide this block from display')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function getContentBlocks(): array
    {
        $blocks = [
            // Default blocks
            static::getSectionWithImageBlock(),
            static::getImageWithTextBlock(),
            static::getSimpleBlock(),
            static::getSectionWithLinkBlock(),
            static::getCompleteBlock(),
            static::getCounterBlock(),
            static::getGalleryBlock(),
            static::getVideoBlock(),
            static::getHotspotBlock(),
        ];

        // Automatically load custom blocks from CustomContentBlocks (main app) if it exists
        if (class_exists('\App\Support\CustomContentBlocks')) {
            $customBlocks = \App\Support\CustomContentBlocks::getCustomBlocks();
            $blocks = array_merge($blocks, $customBlocks);
        }

        return $blocks;
    }
}