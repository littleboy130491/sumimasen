<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Builder as FormsBuilder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use FilamentTiptapEditor\TiptapEditor;

trait HasContentBlocks
{
    // ==========================================
    // REUSABLE FIELD SET BLOCKS
    // ==========================================

    private static function getSliderBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('slider')
            ->label('Slider/Carousel')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->required()
                    ->placeholder('hero-banner')
                    ->helperText('Unique identifier for this block (used for positioning)'),

                Repeater::make('slides')
                    ->label('Slides')
                    ->schema([
                        Select::make('type')
                            ->label('Slide Type')
                            ->options([
                                'video' => 'Video Background',
                                'image' => 'Image Background'
                            ])
                            ->required()
                            ->default('image'),

                        TextInput::make('title')
                            ->label('Title')
                            ->required(),

                        Textarea::make('description')
                            ->label('Description'),

                        TextInput::make('button_text')
                            ->label('Button Text'),

                        TextInput::make('button_url')
                            ->label('Button URL'),

                        TextInput::make('video_url')
                            ->label('Video URL')
                            ->visible(fn(Get $get) => $get('type') === 'video'),

                        CuratorPicker::make('fallback_image')
                            ->label('Fallback/Thumbnail Image')
                            ->acceptedFileTypes(['image/*']),

                        CuratorPicker::make('background_image')
                            ->label('Background Image')
                            ->acceptedFileTypes(['image/*'])
                            ->visible(fn(Get $get) => $get('type') === 'image'),
                    ])
                    ->defaultItems(1)
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    private static function getSectionWithItemsBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('section_with_items')
            ->label('Section with Items')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->required()
                    ->placeholder('layanan-home')
                    ->helperText('Unique identifier for this block (used for positioning)'),

                TextInput::make('section_label')
                    ->label('Section Label'),

                TextInput::make('title')
                    ->label('Section Title')
                    ->required(),

                Textarea::make('description')
                    ->label('Section Description'),

                CuratorPicker::make('background_image')
                    ->label('Background Image')
                    ->acceptedFileTypes(['image/*']),

                Repeater::make('items')
                    ->label('Items')
                    ->schema([
                        TextInput::make('number')
                            ->label('Number/Order'),
                        TextInput::make('title')
                            ->label('Item Title')
                            ->required(),
                        Textarea::make('description')
                            ->label('Item Description'),
                        TextInput::make('url')
                            ->label('Item URL'),
                        CuratorPicker::make('image')
                            ->label('Item Image')
                            ->acceptedFileTypes(['image/*']),
                    ])
                    ->defaultItems(3)
                    ->columnSpanFull()
            ])
            ->columns(2);
    }

    private static function getContentWithMediaBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('content_with_media')
            ->label('Content with Media')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->required()
                    ->placeholder('about-home')
                    ->helperText('Unique identifier for this block (used for positioning)'),

                TextInput::make('section_label')
                    ->label('Section Label'),

                TextInput::make('title')
                    ->label('Title')
                    ->required(),

                TiptapEditor::make('description')
                    ->label('Description')
                    ->profile('simple')
                    ->columnSpanFull(),

                CuratorPicker::make('primary_image')
                    ->label('Primary Image')
                    ->acceptedFileTypes(['image/*']),

                CuratorPicker::make('secondary_image')
                    ->label('Secondary Image')
                    ->acceptedFileTypes(['image/*']),

                Repeater::make('additional_media')
                    ->label('Additional Media/Icons')
                    ->schema([
                        CuratorPicker::make('image')
                            ->label('Image')
                            ->acceptedFileTypes(['image/*']),
                        TextInput::make('alt_text')
                            ->label('Alt Text'),
                    ])
                    ->columnSpanFull(),

                TextInput::make('button_text')
                    ->label('Button Text'),

                TextInput::make('button_url')
                    ->label('Button URL'),
            ])
            ->columns(2);
    }

    private static function getCountersBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('counters')
            ->label('Statistics/Counters')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->required()
                    ->placeholder('hero-counters')
                    ->helperText('Unique identifier for this block (used for positioning)'),

                TextInput::make('title')
                    ->label('Counter Section Title'),

                Repeater::make('counters')
                    ->label('Counters')
                    ->schema([
                        TextInput::make('number')
                            ->label('Number')
                            ->numeric()
                            ->required(),
                        TextInput::make('unit')
                            ->label('Unit'),
                        TextInput::make('label')
                            ->label('Label')
                            ->required(),
                        TextInput::make('prefix')
                            ->label('Prefix'),
                        TextInput::make('suffix')
                            ->label('Suffix'),
                    ])
                    ->defaultItems(4)
                    ->columnSpanFull()
            ])
            ->columns(1);
    }

    private static function getVideoEmbedBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('video_embed')
            ->label('Video Embed')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->required()
                    ->placeholder('video-home')
                    ->helperText('Unique identifier for this block (used for positioning)'),

                TextInput::make('title')
                    ->label('Video Title'),

                TextInput::make('video_url')
                    ->label('Video URL')
                    ->required()
                    ->helperText('YouTube, Vimeo, or direct video URL'),

                CuratorPicker::make('thumbnail_image')
                    ->label('Custom Thumbnail')
                    ->acceptedFileTypes(['image/*'])
                    ->helperText('Optional: custom thumbnail image'),

                Textarea::make('description')
                    ->label('Video Description'),
            ])
            ->columns(2);
    }

    private static function getLogoGridBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('logo_grid')
            ->label('Logo Grid')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->required()
                    ->placeholder('tenant-home')
                    ->helperText('Unique identifier for this block (used for positioning)'),

                TextInput::make('section_label')
                    ->label('Section Label'),

                TextInput::make('title')
                    ->label('Section Title'),

                Repeater::make('logos')
                    ->label('Logos')
                    ->schema([
                        CuratorPicker::make('logo')
                            ->label('Logo Image')
                            ->acceptedFileTypes(['image/*'])
                            ->required(),
                        TextInput::make('alt_text')
                            ->label('Alt Text'),
                        TextInput::make('link_url')
                            ->label('Link URL (optional)'),
                    ])
                    ->defaultItems(6)
                    ->columnSpanFull()
            ])
            ->columns(2);
    }

    private static function getTextSectionBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('text_section')
            ->label('Text Section')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->required()
                    ->placeholder('fasilitas-home')
                    ->helperText('Unique identifier for this block (used for positioning)'),

                TextInput::make('section_label')
                    ->label('Section Label'),

                TextInput::make('title')
                    ->label('Title')
                    ->required(),

                TiptapEditor::make('description')
                    ->label('Content')
                    ->profile('simple')
                    ->columnSpanFull(),

                TextInput::make('button_text')
                    ->label('Button Text'),

                TextInput::make('button_url')
                    ->label('Button URL'),

                CuratorPicker::make('background_image')
                    ->label('Background Image')
                    ->acceptedFileTypes(['image/*']),
            ])
            ->columns(2);
    }

    private static function getTabbedContentBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('tabbed_content')
            ->label('Tabbed Content')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->required()
                    ->placeholder('tab')
                    ->helperText('Unique identifier for this block (used for positioning)'),

                TextInput::make('section_title')
                    ->label('Section Title'),

                Repeater::make('tabs')
                    ->label('Tabs')
                    ->schema([
                        TextInput::make('tab_title')
                            ->label('Tab Title')
                            ->required(),
                        TextInput::make('content_title')
                            ->label('Content Title'),
                        CuratorPicker::make('image')
                            ->label('Tab Image')
                            ->acceptedFileTypes(['image/*']),
                        TiptapEditor::make('content')
                            ->label('Tab Content')
                            ->profile('simple')
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(3)
                    ->columnSpanFull()
            ])
            ->columns(1);
    }

    // ==========================================
    // ORIGINAL GENERAL PURPOSE BLOCKS (Enhanced)
    // ==========================================

    private static function getCompleteBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('complete')
            ->label('Complete Content')
            ->schema([
                TextInput::make('heading'),
                TextInput::make('group'),
                TiptapEditor::make('description')
                    ->profile('simple')
                    ->columnSpan('full')
                    ->extraInputAttributes(['style' => 'min-height: 12rem;']),
                TextInput::make('cta_label')->label('CTA Label'),
                TextInput::make('cta_url')->label('CTA URL'),
                CuratorPicker::make('media_id')
                    ->label('Media')
                    ->helperText('Accepted file types: image or document'),
            ])
            ->columns(2);
    }

    private static function getSimpleBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('simple')
            ->label('Simple Text')
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
            ->label('Video Content')
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
            ->label('Interactive Hotspot')
            ->schema([
                TextInput::make('heading'),
                TextInput::make('sub_heading')->label('Sub Heading'),
                TiptapEditor::make('description')
                    ->profile('simple')
                    ->columnSpan('full')
                    ->extraInputAttributes(['style' => 'min-height: 12rem;']),
                TextInput::make('top')
                    ->numeric()
                    ->label('Top Position (%)'),
                TextInput::make('left')
                    ->numeric()
                    ->label('Left Position (%)'),
                CuratorPicker::make('media_id')
                    ->label('Background Media')
                    ->helperText('Accepted file types: image or document'),
            ])
            ->columns(2);
    }

    private static function getGalleryBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('gallery')
            ->label('Image Gallery')
            ->schema([
                TextInput::make('title')
                    ->label('Gallery Title'),
                CuratorPicker::make('media_id')
                    ->label('Gallery Images')
                    ->multiple()
                    ->acceptedFileTypes(['image/*'])
                    ->helperText('Select multiple images for the gallery'),
            ])
            ->columns(1);
    }

    private static function getImageWithTextBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('image_with_text')
            ->label('Image with Text')
            ->schema([
                CuratorPicker::make('media_id')
                    ->label('Image')
                    ->acceptedFileTypes(['image/*'])
                    ->helperText('Main image for this block'),
                TextInput::make('heading'),
                TiptapEditor::make('description')
                    ->label('Text Content')
                    ->profile('simple')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getCounterBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('counter')
            ->label('Single Counter')
            ->schema([
                TextInput::make('heading'),
                TextInput::make('number')
                    ->numeric()
                    ->label('Counter Number'),
                TextInput::make('prefix')
                    ->label('Counter Prefix'),
                TextInput::make('suffix')
                    ->label('Counter Suffix'),
                TextInput::make('label')
                    ->label('Counter Label'),
            ])
            ->columns(2);
    }

    protected static function getContentBlocks(): array
    {
        return [
            // Advanced Reusable Blocks
            static::getSliderBlock(),
            static::getSectionWithItemsBlock(),
            static::getContentWithMediaBlock(),
            static::getCountersBlock(),
            static::getVideoEmbedBlock(),
            static::getLogoGridBlock(),
            static::getTextSectionBlock(),
            static::getTabbedContentBlock(),

            // General Purpose Blocks
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
