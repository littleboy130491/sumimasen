<?php

namespace Afatmustafa\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum OpenGraphTypes: string implements HasLabel
{
    case ARTICLE = 'article';
    case BOOK = 'book';
    case PROFILE = 'profile';
    /*
     * TODO: Implement the following Open Graph types
     */
    //case MUSIC_SONG = 'music.song';
    //case MUSIC_ALBUM = 'music.album';
    //case MUSIC_PLAYLIST = 'music.playlist';
    //case MUSIC_RADIO_STATION = 'music.radio_station';
    //case VIDEO_MOVIE = 'video.movie';
    //case VIDEO_EPISODE = 'video.episode';
    //case VIDEO_TV_SHOW = 'video.tv_show';
    //case VIDEO_OTHER = 'video.other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ARTICLE => __('seo-suite::seo-suite.opengraph.og_types.article.article_label'),
            self::BOOK => __('seo-suite::seo-suite.opengraph.og_types.book.book_label'),
            self::PROFILE => __('seo-suite::seo-suite.opengraph.og_types.profile.profile_label'),
            //self::MUSIC_SONG => __('seo-suite::seo-suite.opengraph.og_types.music.song_label'),
            //self::MUSIC_ALBUM => __('seo-suite::seo-suite.opengraph.og_types.music.album_label'),
            //self::MUSIC_PLAYLIST => __('seo-suite::seo-suite.opengraph.og_types.music.playlist_label'),
            //self::MUSIC_RADIO_STATION => __('seo-suite::seo-suite.opengraph.og_types.music.radio_station_label'),
            //self::VIDEO_MOVIE => __('seo-suite::seo-suite.opengraph.og_types.video.movie_label'),
            //self::VIDEO_EPISODE => __('seo-suite::seo-suite.opengraph.og_types.video.episode_label'),
            //self::VIDEO_TV_SHOW => __('seo-suite::seo-suite.opengraph.og_types.video.tv_show_label'),
            //self::VIDEO_OTHER => __('seo-suite::seo-suite.opengraph.og_types.video.other_label'),
        };
    }
}
