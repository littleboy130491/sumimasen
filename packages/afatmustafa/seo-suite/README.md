# SEO Suite for FilamentPHP: Easily integrate and manage SEO features in your Filament projects

[![Latest Version on Packagist](https://img.shields.io/packagist/v/afatmustafa/seo-suite.svg?style=flat-square)](https://packagist.org/packages/afatmustafa/seo-suite)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/afatmustafa/seo-suite/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/afatmustafa/seo-suite/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/afatmustafa/seo-suite/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/afatmustafa/seo-suite/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/afatmustafa/seo-suite.svg?style=flat-square)](https://packagist.org/packages/afatmustafa/seo-suite)

Elevate your Filament project with SEO Suite, leveraging the artesaos/seotools package for enhanced search engine optimization.
This package allows you to seamlessly integrate and manage SEO features, making your project more search engine friendly.
![SEO Suite](https://afat.me/projects/seo-suite-pro/art/cover.jpg)

Features
--------

-   Ready-to-use form component: Easily add SEO settings to your resources.
-   Set title and meta tags: Quickly configure title and meta tags.
-   Social media integration: Support for Twitter Cards and Open Graph.
-   Multiple open graph types: Supports 3 open graph types: article, book, profile.
-   Multiple twitter card types: Supports 4 twitter card types: summary, summary_large_image, app, player.
-   Fallback ready: Ensures robust default values.
-   Ready-to-translate: Supports multiple languages.

## Installation

To install the package you should add the following lines to your composer.json file in the repositories key in order to get access to the private package:

```json
{
  "repositories": [
    {
      "type": "composer",
      "url": "https://seo-suite-for-filamentphp.composer.sh"
    }
  ]
}
```
Next, you should require the package via the command line. You will be prompted for your username (which is your e-mail) and your password (which is your license key, e.g. f6183128-5477-4f38-45b1-4842484f4a842:ProjectName).

```bash
composer require afatmustafa/seo-suite
```

> **Note**: This package uses artesaos/seotools behind the scenes, you may also need to follow the [installation](https://github.com/artesaos/seotools#installation) steps there.

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="seo-suite-migrations"
php artisan migrate
```

You can publish the config file with (optional):

```bash
php artisan vendor:publish --tag="seo-suite-config"
```

This is the contents of the published config file:

```php
return [
    /**
     * Override the SEO Model to perform custom actions.
     */
    'model' => \Afatmustafa\SeoSuite\Models\SeoSuite::class,
    'table_name' => 'seo_suite',

    /*
    |--------------------------------------------------------------------------
    | Enabled features.
    | If you want to disable a feature, set it to false.
    |--------------------------------------------------------------------------
    */
    'features' => [
        'general' => [
            'enabled' => true,
            'fields' => [
                'title' => true,
                'description' => true,
            ],
        ],
        'advanced' => [
            'enabled' => true,
            'fields' => [
                'canonical' => true,
                'noindex' => true,
                'nofollow' => true,
                'metas' => true,
            ],
        ],
        'opengraph' => [
            'enabled' => true,
            'fields' => [
                'og_title' => true,
                'og_description' => true,
                'og_type' => true,
                'og_properties' => true,
            ],
        ],
        'x' => [
            'enabled' => true,
            'fields' => [
                'x_card_type' => true,
                'x_title' => true,
                'x_site' => true,
            ],
        ],
    ],
    // SEO fallbacks
    'fallbacks' => [
        'title' => 'title',
        'description' => 'description',
        'og_type' => 'article',
    ],
];
```

Optionally, you can publish the translations using

```bash
php artisan vendor:publish --tag="seo-suite-translations"
```
The package currently supports both English and Turkish languages. If you want to add a new language, feel free to create a PR.

## Usage

### In Your Model

First, you need to add the `Afatmustafa\SeoSuite\Models\Traits\InteractsWithSeoSuite` trait to your model.

If you want to edit your SEO Fallbacks on a model basis, you can edit them by adding a property called `$seoFallbacks` to your model.

```php
// App/Models/Page.php
class Page extends Model {

    use Afatmustafa\SeoSuite\Models\Traits\InteractsWithSeoSuite;
    
     protected ?array $seoFallbacks = [
        'title' => 'name',
        'description' => 'excerpt'
    ];
}
```

### In Your Resource
To use the form component, you can add `\Afatmustafa\SeoSuite\SeoSuite::make()` function into your resource.
```php
// App\Filament\Resources\PageResource.php
class PageResource extends Resource
{
...
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            ...
               Forms\Components\Section::make('SEO Settings')
                    ->schema([
                    \Afatmustafa\SeoSuite\SeoSuite::make()
                    ])
            ...
            ]);
    }
...
}
```

### In Your Frontend Controller
In your controller you can add the trait `Afatmustafa\SeoSuite\Traits\SetsSeoSuite` and then use the `setsSeo()` function to set the meta tags.
```php
// App\Http\Controllers\Site\PageController.php
class PageController extends Controller
{
    use \Afatmustafa\SeoSuite\Traits\SetsSeoSuite;

    public function home() {
        $page = \App\Models\Page::whereTemplate('homepage')->first();
        $this->setsSeo($page);
        return view('site.pages.home');
    }
}
```

### In Your View
You can use the `SEO::generate()` to render the SEO tags in your view.
```blade
<html>
<head>
    {!! SEO::generate() !!}
</head>
<body>

</body>
</html>
```

## Result 🤩

### Frontend Result
Finally, this is how your meta tags will look like in the frontend.
```html
<title>Taking Notes on Taking Notes - Afat.me</title>
<meta name="description" content="I’ll share my personal journey through various note-taking applications, from Notion to Obsidian, highlighting the pros, cons, and humorous moments along the way.">
<meta name="google-site-verification" content="lxqpno64dlwihrdkvbgwtzzha0poig">
<link rel="canonical" href="https://afat.me">
<meta property="og:title" content="My Note-Taking Apps and Experiences">
<meta property="og:description" content="Taking notes and being organized is crucial for boosting your productivity as a developer. There are many tools available for taking notes, such as Notion, Craft, and Obsidian. These tools can help you organize your ideas, projects, and daily tasks.">
<meta property="og:type" content="article">
<meta property="article:tag" content="development">
<meta property="article:author:gender" content="male">
<meta property="article:author:username" content="afatmustafa">
<meta property="article:author:last_name" content="Afat">
<meta property="article:author:first_name" content="Mustafa">
<meta property="article:modified_time" content="2024-08-01 00:00:00">
<meta property="article:published_time" content="2024-08-01 00:00:00">
<meta name="twitter:title" content="My Note-Taking Apps and Experiences">
<meta name="twitter:description" content="I’ll share my personal journey through various note-taking applications, from Notion to Obsidian, highlighting the pros, cons, and humorous moments along the way.">
<meta name="twitter:site" content="https://afat.me">
<meta name="twitter:card" content="summary">
<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","name":"Taking Notes on Taking Notes","description":"I’ll share my personal journey through various note-taking applications, from Notion to Obsidian, highlighting the pros, cons, and humorous moments along the way."}</script>
```

### Panel Result
![SEO Suite](https://afat.me/projects/seo-suite-pro/art/seo-suite.gif)

### Supported Open Graph Types
![Supported OG Types](https://afat.me/projects/seo-suite-pro/art/supported-opengraph-types.gif)

## Default Values
You can set default values for your SEO tags in the artesaos/seotools config file.
```php
// config/seotools.php
````

## Roadmap

- [ ] JSON-LD support
- [ ] Multi-language support with spatie/laravel-translatable
- [ ] OG Image support with spatie/laravel-medialibrary
- [ ] Admin page for managing default SEO values like prefix, suffix, etc.
- [ ] Cache support
- [ ] More Open Graph types
  - [ ] Music / Song
  - [ ] Music / Album
  - [ ] Music / Playlist
  - [ ] Music / Radio Station
  - [ ] Video / Movie
  - [ ] Video / Episode
  - [ ] Video / TV Show
  - [ ] Video / Other


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mustafa Afat](https://github.com/afatmustafa)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
