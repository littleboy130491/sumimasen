<?php

return [
    // General
    'general' => [
        'tab_label' => 'General',
        'meta_title_label' => 'Meta Title',
        'meta_title_hint' => 'If left empty, the title of the page will be used.',
        'meta_title_helper' => 'The title of your page as it will appear in search engine results.',
        'meta_description_label' => 'Meta Description',
        'meta_description_hint' => 'If left empty, the description of the page will be used.',
        'meta_description_helper' => 'A short description of your page as it will appear in search engine results.',
    ],

    // Advanced
    'advanced' => [
        'tab_label' => 'Advanced',
        'canonical_url_label' => 'Canonical URL',
        'canonical_url_hint' => 'If left empty, the URL of the page will be used.',
        'canonical_url_helper' => 'The canonical URL of the page that you want search engines to treat as authoritative.',
        'noindex_label' => 'Noindex',
        'noindex_hint' => 'If checked, search engines will not index this page.',
        'nofollow_label' => 'Nofollow',
        'nofollow_hint' => 'If checked, search engines will not follow links on this page.',

        // Meta Tags
        'metas' => [
            'metas_label' => 'Meta Tags',
            'add_meta_label' => 'Add Meta Tag',
            'meta_types' => [
                'name' => 'Name',
                'property' => 'Property',
                'http_equiv' => 'Http Equiv',
                'charset' => 'Charset',
                'item_prop' => 'Item Prop',
            ],
            'meta_type_label' => 'Meta',
            'meta_type_hint' => '',
            'meta_type_placeholder' => 'Please select a meta type',
            'meta_label' => 'Meta',
            'meta_hint' => '',
            'content_label' => 'Content',
            'content_hint' => '',
        ],
    ],

    // Open Graph
    'opengraph' => [
        'tab_label' => 'Open Graph',
        'og_title_label' => 'Open Graph Title',
        'og_title_hint' => 'If left empty, the title of the page will be used.',
        'og_title_helper' => '',
        'og_description_label' => 'Open Graph Description',
        'og_description_hint' => 'If left empty, the description of the page will be used.',
        'og_description_helper' => '',
        'og_image_label' => 'Open Graph Image',
        'og_image_hint' => 'If left empty, the first image of the page will be used if exist.',
        'og_type_label' => 'Open Graph Type',
        'og_type_hint' => 'If left blank, it will be used as an "article".',
        'og_types' => [
            'article' => [
                'article_label' => 'Article',
                'article_type_details_label' => 'Article Type Details',
                'published_time_label' => 'Published Time',
                'modified_time_label' => 'Modified Time',
                'expiration_time_label' => 'Expiration Time',
                'author_label' => 'Author',
                'section_label' => 'Section',
                'tag_label' => 'Tag',
            ],
            'book' => [
                'book_label' => 'Book',
                'book_type_details_label' => 'Book Type Details',
                'author_label' => 'Author',
                'isbn_label' => 'ISBN',
                'release_date_label' => 'Release Date',
                'tag_label' => 'Tag',
            ],
            'profile' => [
                'profile_label' => 'Profile',
                'profile_type_details_label' => 'Profile Type Details',
                'first_name_label' => 'First Name',
                'last_name_label' => 'Last Name',
                'username_label' => 'Username',
                'gender_label' => 'Gender',
            ],
            'music' => [
                'song_label' => 'Song',
                'song_type_details_label' => 'Music / Song Type Details',
                'duration_label' => 'Duration',
                'album_label' => 'Album',
                'album_disc_label' => 'Album Disc',
                'album_track_label' => 'Album Track',
                'musician_label' => 'Musician',

                'album_type_details_label' => 'Music / Album Type Details',
                'release_date_label' => 'Release Date',

                'playlist_label' => 'Playlist',
                'playlist_type_details_label' => 'Music / Playlist Type Details',
                'song_disc_label' => 'Song Disc',
                'song_track_label' => 'Song Track',
                'creator_label' => 'Creator',

                'radio_station_label' => 'Radio Station',
                'radio_station_type_details_label' => 'Music / Radio Station Type Details',
            ],
            'video' => [
                'movie_label' => 'Movie',
                'movie_type_details_label' => 'Video / Movie Type Details',
                'actor_label' => 'Actor',
                'actor_role_label' => 'Actor Role',
                'director_label' => 'Director',
                'writer_label' => 'Writer',
                'duration_label' => 'Duration',
                'release_date_label' => 'Release Date',
                'tag_label' => 'Tag',

                'episode_label' => 'Episode',
                'episode_type_details_label' => 'Video / Episode Type Details',
                'series_label' => 'Series',

                'tv_show_label' => 'TV Show',
                'tv_show_type_details_label' => 'Video / TV Show Type Details',

                'other_label' => 'Other',
                'other_type_details_label' => 'Video / Other Type Details',
            ],

        ],
        'og_properties' => [
            'og_properties_label' => 'Open Graph Properties',
            'add_og_property_label' => 'Add Open Graph Property',
            'key_label' => 'Key',
            'key_hint' => '',
            'value_label' => 'Value',
            'value_hint' => '',
        ],

    ],

    // X (Formerly Twitter)
    'x' => [
        'tab_label' => 'X (Formerly Twitter)',
        'card_types' => [
            'card_type_label' => 'Card Type',
            'card_type_hint' => 'If left blank, it will be used as an "summary".',
            'summary' => 'Summary',
            'summary_large_image' => 'Summary Large Image',
            'app' => 'App',
            'player' => 'Player',
        ],
        'x_title_label' => 'Title',
        'x_title_hint' => '',
        'x_title_helper' => '',
        'x_site_label' => 'Site',
        'x_site_hint' => '',
        'x_site_helper' => '',
    ],
];
