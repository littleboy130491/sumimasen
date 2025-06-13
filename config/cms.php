<?php

return [
    'site_name' => env('APP_NAME', 'Clean CMS'),
    'site_description' => env('CMS_SITE_DESCRIPTION', 'My CMS Description'),
    'site_url' => env('APP_URL', 'http://localhost'),
    'site_email' => env('CMS_SITE_EMAIL'),
    'site_phone' => env('CMS_SITE_PHONE'),
    'site_logo' => env('CMS_SITE_LOGO', 'logo.png'),
    'site_favicon' => env('CMS_SITE_FAVICON', 'favicon.ico'),
    'site_social_media' => [
        'facebook' => env('CMS_FACEBOOK'),
        'twitter' => env('CMS_TWITTER'),
        'instagram' => env('CMS_INSTAGRAM'),
        'linkedin' => env('CMS_LINKEDIN'),
        'youtube' => env('CMS_YOUTUBE'),
        'whatsapp' => env('CMS_WHATSAPP'),
    ],
    'site_contact' => [
        'email1' => env('CMS_SITE_EMAIL'),
        'email2' => env('CMS_SITE_EMAIL2'),
        'phone1' => env('CMS_SITE_PHONE'),
        'phone2' => env('CMS_SITE_PHONE2'),
        'address1' => env('CMS_ADDRESS'),
        'short_address1' => env('CMS_SHORT_ADDRESS'),
        'address2' => env('CMS_ADDRESS2'),
        'link_address1' => env('CMS_LINK_ADDRESS'),
        'link_address2' => env('CMS_LINK_ADDRESS2'),
        'contact_map' => env('CMS_CONTACT_MAP'),
    ],
    'site_social_media_enabled' => env('CMS_SOCIAL_MEDIA_ENABLED', true),

    'multilanguage_enabled' => env('MULTILANGUAGE_ENABLED', true),

    'default_language' => env('APP_LOCALE', 'en'),

    'language_available' => [
        'id' => 'Indonesian',
        'en' => 'English',

        'zh-cn' => 'Chinese',
        'ko' => 'Korean',
    ],

    'content_models' => [
        'pages' => [
            'model' => Littleboy130491\Sumimasen\Models\Page::class,
            'name' => 'Pages',
            'type' => 'content',
            'has_archive' => false,
            'has_single' => true,
        ],
        'posts' => [
            'model' => Littleboy130491\Sumimasen\Models\Post::class,
            'name' => 'Posts',
            'type' => 'content',
            'has_archive' => true,
            'has_single' => true,
            'archive_SEO_title' => 'Archive: Posts',
            'archive_SEO_description' => 'Archive of all posts',

        ],
        'categories' => [
            'model' => Littleboy130491\Sumimasen\Models\Category::class,
            'name' => 'Categories',
            'type' => 'taxonomy',
            'has_archive' => true,
            'has_single' => false,
            'display_content_from' => 'posts', // the relationship name in the model

        ],
        'tags' => [
            'model' => Littleboy130491\Sumimasen\Models\Tag::class,
            'name' => 'Tags',
            'type' => 'taxonomy',
            'has_archive' => true,
            'has_single' => false,
            'display_content_from' => 'posts', // the relationship name in the model

        ],
    ],

    // fallback content type works when the page slug is not found, it will try to find the content from this type
    // ex: "about-us slug" does not exist in Pages, it will try to find in Posts
    'fallback_content_type' => 'posts',

    'static_page_model' => Littleboy130491\Sumimasen\Models\Page::class,
    'static_page_slug' => 'pages',
    'front_page_slug' => 'beranda',

    'pagination_limit' => env('CMS_PAGINATION_LIMIT', 12),
    'commentable_resources' => [
        Littleboy130491\Sumimasen\Models\Post::class => Littleboy130491\Sumimasen\Filament\Resources\PostResource::class,
        Littleboy130491\Sumimasen\Models\Page::class => Littleboy130491\Sumimasen\Filament\Resources\PageResource::class,
    ],

    'navigation_menu_locations' => [
        'header' => 'Header',
        'footer' => 'Footer',
    ],

    'debug_mode' => [
        'enabled' => env('CMS_DEBUG_MODE_ENABLED', true),
        'environments' => ['local', 'development'],
        'max_variable_depth' => 20,
        'max_array_items' => 50,
        'include_queries' => true,
        'include_cache_info' => true,
        'redacted_keys' => ['password', 'token', 'secret', 'key', 'api_key'],
    ],

    'instagram' => [
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
    ],

];
