<?php

namespace Littleboy130491\Sumimasen\Filament\Forms\Components;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Littleboy130491\SeoSuite\Enums\MetaTypes;
use Littleboy130491\SeoSuite\Enums\XCardTypes;
use Littleboy130491\SeoSuite\Schemas\OpenGraphSchema;
use Littleboy130491\SeoSuite\SeoSuite;

class SeoFields extends SeoSuite
{
    public static function make()
    {
        return Grid::make()
            ->schema([
                Tabs::make('Label')
                    ->tabs([
                        Tabs\Tab::make('seo-suite::seo-suite.general.tab_label')->translateLabel()
                            ->visible(fn (): bool => config('seo-suite.features.general.enabled'))
                            ->schema([
                                TextInput::make('title')
                                    ->translateLabel()
                                    ->label('seo-suite::seo-suite.general.meta_title_label')
                                    ->hint(__('seo-suite::seo-suite.general.meta_title_hint'))
                                    ->helperText(__('seo-suite::seo-suite.general.meta_title_helper'))
                                    ->maxLength(255)
                                    ->visible(fn (): bool => config('seo-suite.features.general.fields.title')),
                                Textarea::make('description')
                                    ->translateLabel()
                                    ->label('seo-suite::seo-suite.general.meta_description_label')
                                    ->hint(__('seo-suite::seo-suite.general.meta_description_hint'))
                                    ->helperText(__('seo-suite::seo-suite.general.meta_description_helper'))
                                    ->maxLength(255)
                                    ->visible(fn (): bool => config('seo-suite.features.general.fields.description')),
                            ]),
                        Tabs\Tab::make('seo-suite::seo-suite.advanced.tab_label')->translateLabel()
                            ->visible(fn (): bool => config('seo-suite.features.advanced.enabled'))
                            ->schema([
                                TextInput::make('canonical_url')
                                    ->translateLabel()
                                    ->label('seo-suite::seo-suite.advanced.canonical_url_label')
                                    ->hint(__('seo-suite::seo-suite.advanced.canonical_url_hint'))
                                    ->helperText(__('seo-suite::seo-suite.advanced.canonical_url_helper'))
                                    ->visible(fn (): bool => config('seo-suite.features.advanced.fields.canonical')),
                                Grid::make(2)->schema([
                                    Toggle::make('noindex')
                                        ->translateLabel()
                                        ->label('seo-suite::seo-suite.advanced.noindex_label')
                                        ->helperText(__('seo-suite::seo-suite.advanced.noindex_hint'))
                                        ->visible(fn (): bool => config('seo-suite.features.advanced.fields.noindex')),
                                    Toggle::make('nofollow')
                                        ->translateLabel()
                                        ->label('seo-suite::seo-suite.advanced.nofollow_label')
                                        ->helperText(__('seo-suite::seo-suite.advanced.nofollow_hint'))
                                        ->visible(fn (): bool => config('seo-suite.features.advanced.fields.nofollow')),
                                ]),
                                Repeater::make('metas')
                                    ->translateLabel()
                                    ->label('seo-suite::seo-suite.advanced.metas.metas_label')
                                    ->addActionLabel(__('seo-suite::seo-suite.advanced.metas.add_meta_label'))
                                    ->collapsed()
                                    ->cloneable()
                                    ->schema([
                                        Select::make('meta_type')
                                            ->translateLabel()
                                            ->label('seo-suite::seo-suite.advanced.metas.meta_type_label')
                                            ->hint(__('seo-suite::seo-suite.advanced.metas.meta_type_hint'))
                                            ->placeholder(__('seo-suite::seo-suite.advanced.metas.meta_type_placeholder'))
                                            ->searchable()
                                            ->native(false)
                                            ->options(MetaTypes::class)
                                            ->default(MetaTypes::NAME),
                                        TextInput::make('meta')
                                            ->translateLabel()
                                            ->label('seo-suite::seo-suite.advanced.metas.meta_label')
                                            ->hint(__('seo-suite::seo-suite.advanced.metas.meta_hint')),
                                        TextInput::make('content')
                                            ->translateLabel()
                                            ->label('seo-suite::seo-suite.advanced.metas.content_label')
                                            ->hint(__('seo-suite::seo-suite.advanced.metas.content_hint'))
                                            ->columnSpanFull(),
                                    ])
                                    ->itemLabel(fn (array $state) => $state['meta'].' - '.$state['content'])
                                    ->columns(2)
                                    ->visible(fn (): bool => config('seo-suite.features.advanced.fields.metas')),
                            ]),
                        Tabs\Tab::make('seo-suite::seo-suite.opengraph.tab_label')->translateLabel()
                            ->visible(fn (): bool => config('seo-suite.features.opengraph.enabled'))
                            ->schema(OpenGraphSchema::make()),
                        Tabs\Tab::make('seo-suite::seo-suite.x.tab_label')->translateLabel()
                            ->visible(fn (): bool => config('seo-suite.features.x.enabled'))
                            ->schema([
                                ToggleButtons::make('x_card_type')
                                    ->translateLabel()
                                    ->label('seo-suite::seo-suite.x.card_types.card_type_label')
                                    ->helperText(__('seo-suite::seo-suite.x.card_types.card_type_hint'))
                                    ->inline()
                                    ->options(XCardTypes::class)
                                    ->default(XCardTypes::SUMMARY)
                                    ->visible(fn (): bool => config('seo-suite.features.x.fields.x_card_type')),
                                Grid::make()->schema([
                                    TextInput::make('x_title')
                                        ->translateLabel()
                                        ->label('seo-suite::seo-suite.x.x_title_label')
                                        ->hint(__('seo-suite::seo-suite.x.x_title_hint'))
                                        ->helperText(__('seo-suite::seo-suite.x.x_title_helper'))
                                        ->visible(fn (): bool => config('seo-suite.features.x.fields.x_title')),
                                    TextInput::make('x_site')
                                        ->translateLabel()
                                        ->label('seo-suite::seo-suite.x.x_site_label')
                                        ->hint(__('seo-suite::seo-suite.x.x_site_hint'))
                                        ->helperText(__('seo-suite::seo-suite.x.x_site_helper'))
                                        ->visible(fn (): bool => config('seo-suite.features.x.fields.x_site')),
                                ]),
                            ]),
                    ])
                    ->contained(false)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'seo-tabs']),
            ])
            ->relationship('seoSuite')
            ->columns(2);
    }
}
