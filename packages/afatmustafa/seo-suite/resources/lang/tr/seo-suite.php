<?php

return [
    // General
    'general' => [
        'tab_label' => 'Genel',
        'meta_title_label' => 'Meta Başlığı',
        'meta_title_hint' => 'Boş bırakılırsa, sayfanın başlığı kullanılacaktır.',
        'meta_title_helper' => 'Sayfanızın arama motoru sonuçlarında görünecek başlığı.',
        'meta_description_label' => 'Meta Açıklaması',
        'meta_description_hint' => 'Boş bırakılırsa, sayfanın açıklaması kullanılacaktır.',
        'meta_description_helper' => 'Sayfanızın arama motoru sonuçlarında görünecek kısa açıklaması.',
    ],

    // Advanced
    'advanced' => [
        'tab_label' => 'Gelişmiş',
        'canonical_url_label' => 'Kanonik URL',
        'canonical_url_hint' => 'Boş bırakılırsa, sayfanın URL\'si kullanılacaktır.',
        'canonical_url_helper' => 'Arama motorlarının yetkili olarak kabul etmesini istediğiniz sayfanın kanonik URL\'si.',
        'noindex_label' => 'Noindex',
        'noindex_hint' => 'İşaretlenirse, arama motorları bu sayfayı dizine eklemeyecektir.',
        'nofollow_label' => 'Nofollow',
        'nofollow_hint' => 'İşaretlenirse, arama motorları bu sayfadaki bağlantıları takip etmeyecektir.',

        // Meta Tags
        'metas' => [
            'metas_label' => 'Meta Etiketleri',
            'add_meta_label' => 'Meta Etiketi Ekle',
            'meta_types' => [
                'name' => 'Ad',
                'property' => 'Özellik',
                'http_equiv' => 'Http Eşdeğer',
                'charset' => 'Karakter Seti',
                'item_prop' => 'Öğe Özelliği',
            ],
            'meta_type_label' => 'Meta',
            'meta_type_hint' => '',
            'meta_type_placeholder' => 'Lütfen bir meta türü seçin',
            'meta_label' => 'Meta',
            'meta_hint' => '',
            'content_label' => 'İçerik',
            'content_hint' => '',
        ],
    ],

    // Open Graph
    'opengraph' => [
        'tab_label' => 'Open Graph',
        'og_title_label' => 'Open Graph Başlığı',
        'og_title_hint' => 'Boş bırakılırsa, sayfanın başlığı kullanılacaktır.',
        'og_title_helper' => '',
        'og_description_label' => 'Open Graph Açıklaması',
        'og_description_hint' => 'Boş bırakılırsa, sayfanın açıklaması kullanılacaktır.',
        'og_description_helper' => '',
        'og_image_label' => 'Open Graph Görseli',
        'og_image_hint' => 'Boş bırakılırsa, sayfanın ilk görseli kullanılacaktır (varsa).',
        'og_type_label' => 'Open Graph Türü',
        'og_type_hint' => 'Boş bırakılırsa, "makale" olarak kullanılacaktır.',
        'og_types' => [
            'article' => [
                'article_label' => 'Makale',
                'article_type_details_label' => 'Makale Türü Detayları',
                'published_time_label' => 'Yayınlanma Zamanı',
                'modified_time_label' => 'Değiştirilme Zamanı',
                'expiration_time_label' => 'Sona Erme Zamanı',
                'author_label' => 'Yazar',
                'section_label' => 'Bölüm',
                'tag_label' => 'Etiket',
            ],
            'book' => [
                'book_label' => 'Kitap',
                'book_type_details_label' => 'Kitap Türü Detayları',
                'author_label' => 'Yazar',
                'isbn_label' => 'ISBN',
                'release_date_label' => 'Yayın Tarihi',
                'tag_label' => 'Etiket',
            ],
            'profile' => [
                'profile_label' => 'Profil',
                'profile_type_details_label' => 'Profil Türü Detayları',
                'first_name_label' => 'Ad',
                'last_name_label' => 'Soyad',
                'username_label' => 'Kullanıcı Adı',
                'gender_label' => 'Cinsiyet',
            ],
            'music' => [
                'song_label' => 'Şarkı',
                'song_type_details_label' => 'Müzik / Şarkı Türü Detayları',
                'duration_label' => 'Süre',
                'album_label' => 'Albüm',
                'album_disc_label' => 'Albüm Disk',
                'album_track_label' => 'Albüm Parça',
                'musician_label' => 'Müzisyen',

                'album_type_details_label' => 'Müzik / Albüm Türü Detayları',
                'release_date_label' => 'Yayın Tarihi',

                'playlist_label' => 'Çalma Listesi',
                'playlist_type_details_label' => 'Müzik / Çalma Listesi Türü Detayları',
                'song_disc_label' => 'Şarkı Disk',
                'song_track_label' => 'Şarkı Parça',
                'creator_label' => 'Oluşturan',

                'radio_station_label' => 'Radyo İstasyonu',
                'radio_station_type_details_label' => 'Müzik / Radyo İstasyonu Türü Detayları',
            ],
            'video' => [
                'movie_label' => 'Film',
                'movie_type_details_label' => 'Video / Film Türü Detayları',
                'actor_label' => 'Oyuncu',
                'actor_role_label' => 'Oyuncu Rolü',
                'director_label' => 'Yönetmen',
                'writer_label' => 'Senarist',
                'duration_label' => 'Süre',
                'release_date_label' => 'Yayın Tarihi',
                'tag_label' => 'Etiket',

                'episode_label' => 'Bölüm',
                'episode_type_details_label' => 'Video / Bölüm Türü Detayları',
                'series_label' => 'Dizi',

                'tv_show_label' => 'TV Şovu',
                'tv_show_type_details_label' => 'Video / TV Şovu Türü Detayları',

                'other_label' => 'Diğer',
                'other_type_details_label' => 'Video / Diğer Türü Detayları',
            ],

        ],
        'og_properties' => [
            'og_properties_label' => 'Open Graph Özellikleri',
            'add_og_property_label' => 'Open Graph Özelliği Ekle',
            'key_label' => 'Anahtar',
            'key_hint' => '',
            'value_label' => 'Değer',
            'value_hint' => '',
        ],

    ],

    // X (Formerly Twitter)
    'x' => [
        'tab_label' => 'X (Eski Twitter)',
        'card_types' => [
            'card_type_label' => 'Kart Türü',
            'card_type_hint' => 'Boş bırakılırsa, "özet" olarak kullanılacaktır.',
            'summary' => 'Özet',
            'summary_large_image' => 'Büyük Görselli Özet',
            'app' => 'Uygulama',
            'player' => 'Oynatıcı',
        ],
        'x_title_label' => 'Başlık',
        'x_title_hint' => '',
        'x_title_helper' => '',
        'x_site_label' => 'Site',
        'x_site_hint' => '',
        'x_site_helper' => '',
    ],
];
