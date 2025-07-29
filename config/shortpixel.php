<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ShortPixel API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ShortPixel image optimization service.
    | Get your API key from https://shortpixel.com/
    |
    */

    'api_key' => env('SHORTPIXEL_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Compression Settings
    |--------------------------------------------------------------------------
    |
    | Default compression level:
    | 0 = Lossless (no quality loss)
    | 1 = Lossy (recommended, best compression)
    | 2 = Glossy (balanced compression and quality)
    |
    */

    'compression' => env('SHORTPIXEL_COMPRESSION', 1),

    /*
    |--------------------------------------------------------------------------
    | Image Resize Settings
    |--------------------------------------------------------------------------
    |
    | Default resize settings for images.
    | Format: 'widthxheight' or 'widthxheight/type'
    | Type: 1 = outer resize (default), 3 = inner resize
    |
    */

    'resize' => [
        'enabled' => env('SHORTPIXEL_RESIZE_ENABLED', false),
        'width' => env('SHORTPIXEL_RESIZE_WIDTH', 1920),
        'height' => env('SHORTPIXEL_RESIZE_HEIGHT', 1080),
        'type' => env('SHORTPIXEL_RESIZE_TYPE', 1), // 1 = outer, 3 = inner
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Format Creation
    |--------------------------------------------------------------------------
    |
    | Create additional modern format versions of images
    |
    */

    'formats' => [
        'create_webp' => env('SHORTPIXEL_CREATE_WEBP', true),
        'create_avif' => env('SHORTPIXEL_CREATE_AVIF', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Settings
    |--------------------------------------------------------------------------
    |
    | Control how images are processed
    |
    */

    'processing' => [
        'speed' => env('SHORTPIXEL_SPEED', 10), // 1-10, higher = faster but more memory
        'timeout' => env('SHORTPIXEL_TIMEOUT', 300), // API timeout in seconds
        'retry_attempts' => env('SHORTPIXEL_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('SHORTPIXEL_RETRY_DELAY', 5), // seconds between retries
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Backup settings for original images
    |
    */

    'backup' => [
        'enabled' => env('SHORTPIXEL_BACKUP_ENABLED', true),
        'base_path' => env('SHORTPIXEL_BACKUP_PATH', storage_path('app/shortpixel-backups')),
        'cleanup_after_days' => env('SHORTPIXEL_BACKUP_CLEANUP_DAYS', 30), // 0 = never cleanup
    ],

    /*
    |--------------------------------------------------------------------------
    | File Handling
    |--------------------------------------------------------------------------
    |
    | Configure which files to process and how
    |
    */

    'files' => [
        'supported_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'],
        'max_file_size' => env('SHORTPIXEL_MAX_FILE_SIZE', 32 * 1024 * 1024), // 32MB in bytes
        'exclude_patterns' => [
            '*.thumbnail.*',
            '*.thumb.*',
            '*-thumb.*',
            '*-thumbnail.*',
            '*.cache.*',
            'temp/*',
            'cache/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Folder Settings
    |--------------------------------------------------------------------------
    |
    | Default folder scanning settings
    |
    */

    'folders' => [
        'default_path' => env('SHORTPIXEL_DEFAULT_FOLDER', 'media'), // Default folder relative to storage/app/public
        'default_recurse_depth' => env('SHORTPIXEL_RECURSE_DEPTH'), // null = infinite
        'default_exclude_folders' => [
            'node_modules',
            'vendor',
            '.git',
            'cache',
            'tmp',
            'temp',
        ],
        'lock_timeout' => env('SHORTPIXEL_LOCK_TIMEOUT', 360), // seconds (6 minutes)
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Path Mapping
    |--------------------------------------------------------------------------
    |
    | Configuration for URL-based optimization instead of file uploads
    |
    */

    'web_path' => [
        'enabled' => env('SHORTPIXEL_WEB_PATH_ENABLED', false),
        'base_url' => env('SHORTPIXEL_WEB_PATH_BASE_URL', env('APP_URL')),
        'public_path_mapping' => [
            storage_path('app/public') => '/storage',
            public_path() => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure logging for ShortPixel operations
    |
    */

    'logging' => [
        'enabled' => env('SHORTPIXEL_LOGGING_ENABLED', true),
        'channel' => env('SHORTPIXEL_LOG_CHANNEL', 'single'),
        'level' => env('SHORTPIXEL_LOG_LEVEL', 'info'),
        'log_api_responses' => env('SHORTPIXEL_LOG_API_RESPONSES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notifications for optimization completion
    |
    */

    'notifications' => [
        'enabled' => env('SHORTPIXEL_NOTIFICATIONS_ENABLED', false),
        'email' => env('SHORTPIXEL_NOTIFICATION_EMAIL'),
        'slack_webhook' => env('SHORTPIXEL_SLACK_WEBHOOK'),
        'threshold_files' => env('SHORTPIXEL_NOTIFICATION_THRESHOLD', 100), // notify if processing more than X files
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for automatic scheduled optimization
    |
    */

    'scheduled' => [
        'enabled' => env('SHORTPIXEL_SCHEDULED_ENABLED', false),
        'folders' => [
            // storage_path('app/public/uploads'),
            // public_path('images'),
        ],
        'cron_expression' => env('SHORTPIXEL_CRON', '0 2 * * *'), // daily at 2 AM
        'max_files_per_run' => env('SHORTPIXEL_SCHEDULED_MAX_FILES', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Settings
    |--------------------------------------------------------------------------
    |
    | Fine-tune quality settings for different image types
    |
    */

    'quality' => [
        'jpeg_quality' => env('SHORTPIXEL_JPEG_QUALITY', 85),
        'png_quality' => env('SHORTPIXEL_PNG_QUALITY', 90),
        'webp_quality' => env('SHORTPIXEL_WEBP_QUALITY', 85),
        'avif_quality' => env('SHORTPIXEL_AVIF_QUALITY', 85),
        'keep_exif' => env('SHORTPIXEL_KEEP_EXIF', false),
        'cmyk_to_rgb' => env('SHORTPIXEL_CMYK_TO_RGB', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Track performance metrics and optimization statistics
    |
    */

    'monitoring' => [
        'track_savings' => env('SHORTPIXEL_TRACK_SAVINGS', true),
        'track_processing_time' => env('SHORTPIXEL_TRACK_TIME', true),
        'store_optimization_history' => env('SHORTPIXEL_STORE_HISTORY', false),
        'cleanup_history_after_days' => env('SHORTPIXEL_HISTORY_CLEANUP_DAYS', 90),
    ],
];
