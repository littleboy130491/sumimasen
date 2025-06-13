<?php

namespace Afatmustafa\SeoSuite\Schemas;

use Afatmustafa\SeoSuite\Enums\OpenGraphTypes;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

class OpenGraphSchema
{
    public static function make(): array
    {
        return [
            TextInput::make('og_title')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_title_label')
                ->hint(__('seo-suite::seo-suite.opengraph.og_title_hint'))
                ->helperText(__('seo-suite::seo-suite.opengraph.og_title_helper'))
                ->visible(fn (): bool => config('seo-suite.features.opengraph.fields.og_title')),
            Textarea::make('og_description')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_description_label')
                ->hint(__('seo-suite::seo-suite.opengraph.og_description_hint'))
                ->helperText(__('seo-suite::seo-suite.opengraph.og_description_helper'))
                ->visible(fn (): bool => config('seo-suite.features.opengraph.fields.og_description')),
            Grid::make(1)
                ->schema(self::openGraphTypes())
                ->visible(fn (): bool => config('seo-suite.features.opengraph.fields.og_type')),
        ];
    }

    public static function openGraphTypes(): array
    {
        return [
            Select::make('og_type')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_type_label')
                ->hint(__('seo-suite::seo-suite.opengraph.og_type_hint'))
                ->default(OpenGraphTypes::ARTICLE)
                ->options(OpenGraphTypes::class)
                ->live(onBlur: true)
                ->native(false)
                ->searchable(),
            self::ogTypeFields(),
            Repeater::make('og_properties')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_properties.og_properties_label')
                ->addActionLabel(__('seo-suite::seo-suite.opengraph.og_properties.add_og_property_label'))
                ->collapsed()
                ->cloneable()
                ->schema([
                    TextInput::make('key')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_properties.key_label')
                        ->hint(__('seo-suite::seo-suite.opengraph.og_properties.key_hint')),
                    TextInput::make('value')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_properties.value_label')
                        ->hint(__('seo-suite::seo-suite.opengraph.og_properties.value_hint')),
                ])
                ->itemLabel(fn (array $state) => $state['key'] . ' - ' . $state['value'])
                ->columns(2)
                ->visible(fn (): bool => config('seo-suite.features.opengraph.fields.og_properties')),
        ];
    }

    public static function ogTypeFields(): Grid
    {
        return Grid::make()
            ->schema([
                Fieldset::make()
                    ->label('seo-suite::seo-suite.opengraph.og_types.article.article_type_details_label')
                    ->translateLabel()
                    ->schema([self::articleFields()])->visible(fn (Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::ARTICLE->value),
                Fieldset::make()
                    ->label('seo-suite::seo-suite.opengraph.og_types.book.book_type_details_label')
                    ->translateLabel()
                    ->schema([self::bookFields()])->visible(fn (Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::BOOK->value),
                Fieldset::make()
                    ->label('seo-suite::seo-suite.opengraph.og_types.profile.profile_type_details_label')
                    ->translateLabel()
                    ->schema([self::profileFields()])->visible(fn (Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::PROFILE->value),
                //Fieldset::make()
                //    ->label('seo-suite::seo-suite.opengraph.og_types.music.song_type_details_label')
                //    ->translateLabel()
                //    ->schema([self::musicSongFields()])->visible(fn(Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::MUSIC_SONG->value),
                //Fieldset::make()
                //    ->label('seo-suite::seo-suite.opengraph.og_types.music.album_type_details_label')
                //    ->translateLabel()
                //    ->schema([self::musicAlbumFields()])->visible(fn(Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::MUSIC_ALBUM->value),
                //Fieldset::make()
                //    ->label('seo-suite::seo-suite.opengraph.og_types.music.playlist_type_details_label')
                //    ->translateLabel()
                //    ->schema([self::musicPlaylistFields()])->visible(fn(Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::MUSIC_PLAYLIST->value),
                //Fieldset::make()
                //    ->label('seo-suite::seo-suite.opengraph.og_types.music.radio_station_type_details_label')
                //    ->translateLabel()
                //    ->schema([self::musicRadioStationFields()])->visible(fn(Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::MUSIC_RADIO_STATION->value),
                //Fieldset::make()
                //    ->label('seo-suite::seo-suite.opengraph.og_types.video.movie_type_details_label')
                //    ->translateLabel()
                //    ->schema([self::videoMovie()])->visible(fn(Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::VIDEO_MOVIE->value),
                //Fieldset::make()
                //    ->label('seo-suite::seo-suite.opengraph.og_types.video.episode_type_details_label')
                //    ->translateLabel()
                //    ->schema([self::videoEpisode()])->visible(fn(Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::VIDEO_EPISODE->value),
                //Fieldset::make()
                //    ->label('seo-suite::seo-suite.opengraph.og_types.video.tv_show_type_details_label')
                //    ->translateLabel()
                //    ->schema([self::videoTvShow()])->visible(fn(Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::VIDEO_TV_SHOW->value),
                //Fieldset::make()
                //    ->label('seo-suite::seo-suite.opengraph.og_types.video.other_type_details_label')
                //    ->translateLabel()
                //    ->schema([self::videoOther()])->visible(fn(Get $get): bool => (is_object($get('og_type')) ? $get('og_type')->value : $get('og_type')) === OpenGraphTypes::VIDEO_OTHER->value),
            ]);
    }

    public static function articleFields(): Grid
    {
        return Grid::make()->schema([
            Fieldset::make()
                ->label('seo-suite::seo-suite.opengraph.og_types.article.author_label')
                ->translateLabel()
                ->schema([
                    TextInput::make('og_type_details.author.first_name')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_types.profile.first_name_label'),
                    TextInput::make('og_type_details.author.last_name')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_types.profile.last_name_label'),
                    TextInput::make('og_type_details.author.username')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_types.profile.username_label'),
                    TextInput::make('og_type_details.author.gender')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_types.profile.gender_label'),
                ]),
            DateTimePicker::make('og_type_details.published_time')
                ->native(false)
                ->default(now()->format('Y-m-d\TH:i'))
                ->label('seo-suite::seo-suite.opengraph.og_types.article.published_time_label')
                ->translateLabel(),
            DateTimePicker::make('og_type_details.modified_time')
                ->native(false)
                ->default(now()->format('Y-m-d\TH:i'))
                ->label('seo-suite::seo-suite.opengraph.og_types.article.modified_time_label')
                ->translateLabel(),
            DateTimePicker::make('og_type_details.expiration_time')
                ->native(false)
                ->label('seo-suite::seo-suite.opengraph.og_types.article.expiration_time_label')
                ->translateLabel(),
            TextInput::make('og_type_details.section')
                ->label('seo-suite::seo-suite.opengraph.og_types.article.section_label')
                ->translateLabel(),
            TextInput::make('og_type_details.tag')
                ->label('seo-suite::seo-suite.opengraph.og_types.article.tag_label')
                ->translateLabel(),
        ])->columns(2);
    }

    public static function bookFields(): Grid
    {
        return Grid::make()->schema([
            Fieldset::make()
                ->label('seo-suite::seo-suite.opengraph.og_types.book.author_label')
                ->translateLabel()
                ->schema([
                    TextInput::make('og_type_details.author.first_name')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_types.profile.first_name_label'),
                    TextInput::make('og_type_details.author.last_name')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_types.profile.last_name_label'),
                    TextInput::make('og_type_details.author.username')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_types.profile.username_label'),
                    TextInput::make('og_type_details.author.gender')
                        ->translateLabel()
                        ->label('seo-suite::seo-suite.opengraph.og_types.profile.gender_label'),
                ]),
            TextInput::make('og_type_details.isbn')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.book.isbn_label'),
            DateTimePicker::make('og_type_details.release_date')
                ->translateLabel()
                ->native(false)
                ->default(now()->format('Y-m-d\TH:i'))
                ->label('seo-suite::seo-suite.opengraph.og_types.book.release_date_label'),
            TextInput::make('og_type_details.tag')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.book.tag_label'),
        ])->columns(2);
    }

    public static function profileFields(): Grid
    {
        return Grid::make()->schema([
            TextInput::make('og_type_details.first_name')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.profile.first_name_label'),
            TextInput::make('og_type_details.last_name')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.profile.last_name_label'),
            TextInput::make('og_type_details.username')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.profile.username_label'),
            TextInput::make('og_type_details.gender')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.profile.gender_label'),
        ])->columns(2);
    }

    public static function musicSongFields(): Grid
    {
        return Grid::make()->schema([
            TextInput::make('og_type_details.duration')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.duration_label'),
            TextInput::make('og_type_details.album')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.album_label'),
            TextInput::make('og_type_details.album:disc')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.album_disc_label'),
            TextInput::make('og_type_details.album:track')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.album_track_label'),
            TextInput::make('og_type_details.musician')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.musician_label'),
        ])->columns(2);
    }

    public static function musicAlbumFields(): Grid
    {
        return Grid::make()->schema([
            TextInput::make('og_type_details.song')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.song_label'),
            TextInput::make('og_type_details.song:disc')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.album_label'),
            TextInput::make('og_type_details.song:track')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.album_disc_label'),
            TextInput::make('og_type_details.musician')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.musician_label'),
            DateTimePicker::make('og_type_details.release_date')
                ->native(false)
                ->default(now()->format('Y-m-d\TH:i'))
                ->label('seo-suite::seo-suite.opengraph.og_types.music.release_date_label')
                ->translateLabel(),
        ])->columns(2);
    }

    public static function musicPlaylistFields(): Grid
    {
        return Grid::make()->schema([
            TextInput::make('og_type_details.song')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.song_label'),
            TextInput::make('og_type_details.song:disc')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.album_label'),
            TextInput::make('og_type_details.song:track')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.album_disc_label'),
            TextInput::make('og_type_details.creator')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.creator_label'),
        ])->columns(2);
    }

    public static function musicRadioStationFields(): Grid
    {
        return Grid::make()->schema([
            TextInput::make('og_type_details.creator')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.music.creator_label'),
        ])->columns(2);
    }

    public static function videoMovie(): Grid
    {
        return Grid::make()->schema([
            TextInput::make('og_type_details.actor')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.actor_label'),
            TextInput::make('og_type_details.actor:role')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.actor_role_label'),
            TextInput::make('og_type_details.director')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.director_label'),
            TextInput::make('og_type_details.writer')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.writer_label'),
            TextInput::make('og_type_details.duration')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.duration_label'),
            DateTimePicker::make('og_type_details.release_date')
                ->translateLabel()
                ->native(false)
                ->default(now()->format('Y-m-d\TH:i'))
                ->label('seo-suite::seo-suite.opengraph.og_types.video.release_date_label'),
            TextInput::make('og_type_details.tag')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.tag_label'),
        ])->columns(2);
    }

    public static function videoEpisode(): Grid
    {
        return Grid::make()->schema([
            TextInput::make('og_type_details.actor')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.actor_label'),
            TextInput::make('og_type_details.actor:role')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.actor_role_label'),
            TextInput::make('og_type_details.director')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.director_label'),
            TextInput::make('og_type_details.writer')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.writer_label'),
            TextInput::make('og_type_details.duration')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.duration_label'),
            DateTimePicker::make('og_type_details.release_date')
                ->translateLabel()
                ->native(false)
                ->default(now()->format('Y-m-d\TH:i'))
                ->label('seo-suite::seo-suite.opengraph.og_types.video.release_date_label'),
            TextInput::make('og_type_details.tag')
                ->translateLabel()
                ->label(__('seo-suite::seo-suite.opengraph.og_types.video.tag_label')),
            TextInput::make('og_type_details.series')
                ->translateLabel()
                ->label('advanced-seo-suite::advanced-seo-suite.opengraph.og_types.video.series_label'),
        ])->columns(2);
    }

    public static function videoTvShow(): Grid
    {
        return Grid::make()->schema([
            TextInput::make('og_type_details.actor')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.actor_label'),
            TextInput::make('og_type_details.actor:role')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.actor_role_label'),
            TextInput::make('og_type_details.director')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.director_label'),
            TextInput::make('og_type_details.writer')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.writer_label'),
            TextInput::make('og_type_details.duration')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.duration_label'),
            DateTimePicker::make('og_type_details.release_date')
                ->translateLabel()
                ->native(false)
                ->default(now()->format('Y-m-d\TH:i'))
                ->label('seo-suite::seo-suite.opengraph.og_types.video.release_date_label'),
            TextInput::make('og_type_details.tag')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.tag_label'),
        ])->columns(2);
    }

    public static function videoOther(): Grid
    {
        return Grid::make()->schema([
            TextInput::make('og_type_details.actor')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.actor_label'),
            TextInput::make('og_type_details.actor:role')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.actor_role_label'),
            TextInput::make('og_type_details.director')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.director_label'),
            TextInput::make('og_type_details.writer')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.writer_label'),
            TextInput::make('og_type_details.duration')
                ->numeric()
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.duration_label'),
            DateTimePicker::make('og_type_details.release_date')
                ->translateLabel()
                ->native(false)
                ->default(now()->format('Y-m-d\TH:i'))
                ->label('seo-suite::seo-suite.opengraph.og_types.video.release_date_label'),
            TextInput::make('og_type_details.tag')
                ->translateLabel()
                ->label('seo-suite::seo-suite.opengraph.og_types.video.tag_label'),
        ])->columns(2);
    }
}
