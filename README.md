# Sumimasen CMS

A powerful, multilingual Laravel CMS package built with Filament v3. Features hierarchical content management, role-based permissions, dynamic components, and extensive customization options.

## Features

🌐 **Multilingual Content** - Full translation support with smart fallback and URL redirection  
📝 **Content Management** - Pages, posts, categories, tags, and comments with hierarchical structure  
🔐 **Role-Based Access** - Comprehensive permission system with predefined roles  
🧩 **Dynamic Components** - Flexible component system for reusable content blocks  
📊 **SEO Optimized** - Built-in SEO suite integration and sitemap generation  
📱 **Responsive Admin** - Beautiful Filament-powered admin interface  
🎨 **Template System** - WordPress-like template hierarchy with custom slug support  
📧 **Form System** - Built-in contact forms with email notifications and reCAPTCHA  
📈 **Analytics** - Page views and likes tracking with real-time updates  
🔄 **Scheduled Publishing** - Automatic content publishing with cron integration  
📷 **Media Management** - Advanced media handling with Curator integration  
🎯 **Performance** - Optimized queries, caching, and route resolution  
💬 **Comments System** - Hierarchical comments with moderation and approval workflow

## Requirements

- PHP 8.2+
- Laravel 12.0+
- MySQL 8.0+ / PostgreSQL 13+
- Node.js 18+ (for asset compilation)

## Installation

### Step 1: Install Package

```bash
composer require littleboy130491/cms
```

### Step 2: Run Installation Command

```bash
php artisan cms:install
```

This command will:
- Publish configuration files
- Publish and run migrations
- Publish views and assets
- Generate default permission roles
- Guide you through the setup process

### Step 3: Configure Environment

Add required environment variables to your `.env` file:

```env
# reCAPTCHA (optional but recommended)
NOCAPTCHA_SITEKEY=your_recaptcha_site_key
NOCAPTCHA_SECRET=your_recaptcha_secret_key

# Instagram Integration (optional)
INSTAGRAM_ACCESS_TOKEN=your_instagram_access_token

# Admin Email for Notifications
MAIL_ADMIN_EMAIL=admin@yoursite.com

# Debug Mode (development only)
CMS_DEBUG_MODE_ENABLED=false
```

> **Note**: The CMS plugin is automatically registered with all Filament panels. No manual plugin registration is required!

## Quick Start

### 1. Create Your First Admin User

```bash
php artisan make:filament-user
```

### 2. Access Admin Panel

Visit `/admin` and log in with your newly created admin account.

### 3. Configure General Settings

Navigate to **Settings > General** in the admin panel to configure:
- Site name and description
- Contact information
- Social media links
- Logo and favicon

### 4. Create Your First Content

1. **Pages**: Create static pages like "About", "Contact", etc.
2. **Posts**: Create blog posts or news articles
3. **Categories & Tags**: Organize your content
4. **Menus**: Build navigation menus
5. **Comments**: Manage user comments and feedback

## Configuration

### Content Models Configuration

The CMS supports multiple content types with custom slug support defined in `config/cms.php`:

```php
'content_models' => [
    'pages' => [
        'model' => \Littleboy130491\Sumimasen\Models\Page::class,
        'name' => 'Pages',
        'type' => 'content',
        'has_archive' => false,
        'has_single' => true,
    ],
    'posts' => [
        'model' => \App\Models\Post::class,
        'name' => 'Posts',
        'type' => 'content',
        'has_archive' => true,
        'has_single' => true,
        'archive_SEO_title' => 'Blog Posts',
        'archive_SEO_description' => 'Latest news and articles',
        'eager_load' => ['categories', 'tags'],
    ],
    'categories' => [
        'model' => \Littleboy130491\Sumimasen\Models\Category::class,
        'name' => 'Categories',
        'type' => 'taxonomy',
        'has_archive' => true,
        'has_single' => false,
        'display_content_from' => 'posts',
    ],
    'facilities' => [
        'model' => \App\Models\Facility::class,
        'name' => 'Facilities',
        'type' => 'content',
        'slug' => 'fasilitas', // Custom slug override
        'has_archive' => true,
        'has_single' => true,
    ],
    'commercials' => [
        'model' => \App\Models\Commercial::class,
        'name' => 'Commercials',
        'type' => 'content',
        'slug' => 'area-komersil', // Custom slug override
        'has_archive' => false,
        'has_single' => true,
    ],
    // Add your custom content types here
],
```

#### Custom Slug Support

The CMS supports custom URL slugs different from the configuration key:

- **Without custom slug**: `/facilities` (uses the config key)
- **With custom slug**: `/fasilitas` (uses the custom slug value)

This allows you to have:
- Clean, localized URLs
- SEO-friendly paths
- Maintain internal code organization

### Multilingual Setup

Configure supported languages:

```php
'multilanguage_enabled' => true,
'default_language' => 'en',
'language_available' => [
    'en' => 'English',
    'id' => 'Indonesian',
    'zh' => 'Chinese',
    'ko' => 'Korean',
],
```

### Comments System Configuration

Enable comments on your content models by adding the `HasComments` trait:

```php
use Littleboy130491\Sumimasen\Traits\HasComments;

class Post extends Model
{
    use HasComments;
    
    // Your model code...
}
```

Comments support:
- **Hierarchical Structure**: Nested replies with unlimited depth
- **Moderation Workflow**: Pending, approved, and rejected status
- **Admin Management**: Full CRUD operations in Filament admin
- **Frontend Integration**: Ready-to-use comment components

## Template System

The CMS uses a sophisticated template hierarchy system with WordPress-like template resolution and custom slug support.

### Template Hierarchy

Templates are resolved in order of specificity, checking both your application views and the package's fallback views:

#### 1. Home Page Templates

For the home page (`/`):

```
1. user defined template from CMS
2. templates/home.blade.php                      (content slug in default language)
3. templates/singles/home.blade.php              (specific home template)
4. templates/singles/front-page.blade.php        (front page template)
5. templates/front-page.blade.php                (front page template)
6. templates/singles/default.blade.php           (default single)
7. templates/default.blade.php                   (global default)
```

#### 2. Static Page Templates

For static pages (e.g., `/about`):

```
1. user defined template from CMS
2. templates/about.blade.php                     (content slug in default language)
3. templates/singles/about.blade.php             (specific page by slug)
4. templates/singles/page.blade.php              (all pages)
5. templates/page.blade.php                      (all pages)
6. templates/singles/default.blade.php           (default single)
7. templates/default.blade.php                   (global default)
```

#### 3. Single Content Templates

For single content items with custom slug support (e.g., `/fasilitas/pool-area`):

```
1. user defined template from CMS
2. templates/pool-area.blade.php                      (content slug in default language)
3. templates/singles/fasilitas-pool-area.blade.php    (specific content by slug)
4. templates/singles/fasilitas.blade.php              (all content of this type by slug)
5. templates/fasilitas.blade.php                      (all content of this type)
6. templates/singles/facility-pool-area.blade.php     (fallback by config key)
7. templates/singles/facility.blade.php               (fallback by config key)
8. templates/facility.blade.php                       (fallback by config key)
9. templates/singles/default.blade.php                (default single)
10. templates/default.blade.php                       (global default)
```

#### 4. Archive Templates

For content archives (e.g., `/fasilitas`):

```
1. templates/archives/archive-fasilitas.blade.php     (specific archive by slug)
2. templates/archive-fasilitas.blade.php              (specific archive by slug)
3. templates/archives/archive.blade.php               (default archive)
4. templates/archive.blade.php                        (default archive)
```

#### 5. Taxonomy Templates

For taxonomy archives (e.g., `/categories/technology`):

```
1. user defined template from CMS
2. templates/archives/categories-technology.blade.php (specific taxonomy-term)
3. templates/archives/categories.blade.php            (all terms in taxonomy)
4. templates/categories-technology.blade.php          (specific taxonomy-term)
5. templates/categories.blade.php                     (all terms in taxonomy)
6. templates/archives/archive.blade.php               (default archive)
7. templates/archive.blade.php                        (default archive)
```

### Template Structure

Create your templates in `resources/views/templates/`:

```
templates/
├── default.blade.php                          # Global fallback
├── home.blade.php                             # Home page
├── page.blade.php                             # All static pages
├── custom-hero-layout.blade.php               # User-defined template
├── custom-about-layout.blade.php              # User-defined template
├── about.blade.php                            # Content slug template
├── contact.blade.php                          # Content slug template
├── singles/
│   ├── default.blade.php                     # Default single template
│   ├── page.blade.php                        # All static pages
│   ├── page-about.blade.php                  # Specific page
│   ├── post.blade.php                        # All posts
│   ├── post-featured.blade.php               # Specific post
│   ├── fasilitas.blade.php                   # Custom slug content type
│   └── facility.blade.php                    # Fallback for original key
├───archives/
    ├─── archive.blade.php                     # Default archive
    ├─── archive-posts.blade.php               # Posts archive
    ├─── archive-fasilitas.blade.php           # Custom slug archive
    ├─── categories.blade.php                  # Category taxonomy
    └─── categories-technology.blade.php       # Specific category
```

### Template Context

Each template receives specific variables:

#### All Templates
```php
$lang           // Current language
$bodyClasses    // Generated CSS classes
```

#### Single Content Templates
```php
$item           // The record from CMS
$content_type   // Content type slug
$content_slug   // Content slug
$title          // Content title
```

#### Archive Templates
```php
$items          // Paginated collection
$record         // Record from static page CMS assigned as archive
$archive        // Archive object with metadata
$post_type      // Content type slug
$title          // Archive title
```

#### Taxonomy Templates
```php
$items           // Related content (paginated)
$taxonomy        // Taxonomy key
$taxonomy_slug   // Taxonomy slug
$record          // Taxonomy record from CMS
$title           // Taxonomy title
```


##### Template Body Classes

The system automatically generates CSS classes for styling:

```php
// Example body classes
"lang-en type-post slug-my-post-title"
"lang-id type-fasilitas slug-pool-area"
"lang-en archive-posts archive-page"
"lang-id taxonomy-categories term-technology"
```

Use these in your CSS:

```css
.lang-en .post { /* English posts */ }
.lang-id .fasilitas { /* Indonesian facilities */ }
.archive-posts { /* Posts archive styling */ }
.taxonomy-categories { /* Category taxonomy styling */ }
```

### Performance Optimization

The template system includes several performance optimizations:

1. **Cached Route Resolution**: Routes are cached for 24 hours
2. **Cached Config Lookups**: Content model configs are cached
3. **Eager Loading**: Automatically loads configured relationships
4. **Template Caching**: Laravel's view caching applies to all templates

To clear caches:

```bash
# Clear all CMS caches
php artisan cms:routes-clear

# Clear view cache
php artisan view:clear
```

## Advanced Features

### Comments System

The CMS includes a powerful hierarchical comments system with the following features:

#### Admin Management

**Comments Resource**: Manage all comments from a centralized location with:
- Bulk status updates (approve, reject, pending)
- Content moderation tools
- User information tracking
- Hierarchical relationship viewing

**Relation Managers**: Each commentable model automatically gets a comments relation manager for inline comment management:

```php
// Automatically available in your Filament resources
public static function getRelations(): array
{
    return [
        RelationManagers\CommentsRelationManager::class,
    ];
}
```

#### Model Integration

Add comments to any model:

```php
use Littleboy130491\Sumimasen\Traits\HasComments;

class Article extends Model
{
    use HasComments;
    
    // Now your model has comments relationship
}
```

Access comments in your code:

```php
// Get all comments
$article->comments

// Get only approved comments
$article->approvedComments

// Get Filament edit URL (if resource exists)
$article->getFilamentEditUrl()
```

#### Frontend Integration

Display comments in your templates:

```blade
{{-- Show approved comments --}}
@foreach($post->approvedComments as $comment)
    <div class="comment">
        <h5>{{ $comment->name }}</h5>
        <p>{{ $comment->content }}</p>
        <small>{{ $comment->created_at->diffForHumans() }}</small>
        
        {{-- Show replies --}}
        @if($comment->replies->count())
            <div class="replies ml-4">
                @foreach($comment->replies as $reply)
                    <div class="reply">
                        <h6>{{ $reply->name }}</h6>
                        <p>{{ $reply->content }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach
```

### Dynamic Components

Create reusable content blocks:

1. **Create Component in Admin**: Add a new component with a unique name
2. **Create Blade Template**: `resources/views/components/dynamic/{name}.blade.php`
3. **Use in Templates**: `<x-component-loader name="your-component" />`

Example component template:
```blade
{{-- resources/views/components/dynamic/hero-slider.blade.php --}}
@foreach ($componentData->blocks as $block)
    @if ($block['type'] === 'slide')
        <div class="slide">
            <h2>{{ $block['data']['heading'] }}</h2>
            <p>{{ $block['data']['description'] }}</p>
            @if(isset($block['data']['image_url']))
                <img src="{{ $block['data']['image_url'] }}" alt="{{ $block['data']['heading'] }}">
            @endif
        </div>
    @endif
@endforeach
```

### Livewire Components

Built-in interactive components:

```blade
{{-- Like button with real-time updates --}}
<livewire:sumimasen-cms.like-button :content="$post" />

{{-- Contact form with reCAPTCHA --}}
<livewire:sumimasen-cms.submission-form />

{{-- Comment form (if implementing frontend comments) --}}
<livewire:sumimasen-cms.comment-form :commentable="$post" />
```

### Settings Management

Access site settings in templates:

```blade
@php
    $settings = app(\App\Settings\GeneralSettings::class);
@endphp

<footer>
    <p>{{ $settings->site_name }}</p>
    <p>Email: {{ $settings->email }}</p>
    <p>Phone: {{ $settings->phone_1 }}</p>
</footer>
```

### SEO Features

Automatic SEO optimization:
- Meta titles and descriptions
- Open Graph tags
- Structured data
- XML sitemap generation
- Multilingual hreflang tags

### Debug Mode

Enable comprehensive debugging in development:

```env
CMS_DEBUG_MODE_ENABLED=true
APP_ENV=local
```

Provides detailed HTML comments with:
- Request information
- Database queries
- View data
- Performance metrics

## Scheduled Tasks

Set up cron job for automated features:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Automated tasks include:
- Publishing scheduled content (every 30 minutes)
- Refreshing Instagram tokens (monthly)
- Other maintenance tasks

## Security Features

- CSRF protection on all forms
- XSS prevention with input sanitization
- Role-based access control
- reCAPTCHA integration
- Secure file uploads
- Input validation and filtering
- Comment moderation system

## Performance Optimization

- Query optimization with eager loading
- Response caching support
- Image optimization with Curator
- Queue support for heavy operations
- Database indexing
- Asset minification
- Optimized comment queries with relationship loading
- Cached route resolution for custom slugs

## Customization

### Extending Models

Add traits to your models:

```php
use Littleboy130491\Sumimasen\Traits\HasPageViews;
use Littleboy130491\Sumimasen\Traits\HasPageLikes;
use Littleboy130491\Sumimasen\Traits\HasComments;

class CustomModel extends Model
{
    use HasPageViews, HasPageLikes, HasComments;
}
```

### Custom Resources

Override default resources:

```php
SumimasenPlugin::make()
    ->resources([
        CustomPostResource::class,
        CustomCommentResource::class,
        // ... other resources
    ])
```

### Debug Mode

Enable debug mode for detailed error information:

```env
CMS_DEBUG_MODE_ENABLED=true
APP_DEBUG=true
```

# Extending Content Blocks in Your Main App

This guide explains how to extend the content blocks from the sumimasen package in your main Laravel application without modifying the trait in your resources.

## Step 1: Create a Content Blocks Service Provider

Create a new service provider in your main app at `app/Providers/ContentBlocksServiceProvider.php`:

```bash
php artisan make:provider ContentBlocksServiceProvider
```

Then edit the file to add your custom content blocks:

```php
<?php

namespace App\Providers;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Builder as FormsBuilder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Support\ServiceProvider;

class ContentBlocksServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
    
    /**
     * Register custom content blocks
     * This method is called by the HasContentBlocks trait to add custom blocks
     */
    public function registerCustomContentBlocks(): array
    {
        return [
            $this->getCustomTestimonialBlock(),
            $this->getCustomPricingBlock(),
            // Add more custom blocks here
        ];
    }
    
    /**
     * Example: Custom Testimonial Block
     */
    private function getCustomTestimonialBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('testimonial')
            ->label('Testimonial')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->required(),
                TextInput::make('customer_title')
                    ->label('Customer Title'),
                TiptapEditor::make('testimonial')
                    ->label('Testimonial Content')
                    ->profile('simple')
                    ->columnSpanFull(),
                CuratorPicker::make('customer_image')
                    ->label('Customer Image')
                    ->acceptedFileTypes(['image/*'])
                    ->preserveFilenames(),
                TextInput::make('rating')
                    ->label('Rating')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->default(5),
            ])
            ->columns(2);
    }
    
    /**
     * Example: Custom Pricing Block
     */
    private function getCustomPricingBlock(): FormsBuilder\Block
    {
        return FormsBuilder\Block::make('pricing')
            ->label('Pricing Table')
            ->schema([
                TextInput::make('block_id')
                    ->label('Block ID')
                    ->helperText('Identifier for the block')
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->label('Section Title')
                    ->required(),
                TiptapEditor::make('description')
                    ->label('Section Description')
                    ->profile('simple')
                    ->columnSpanFull(),
                // You can add repeaters for pricing plans here
                TextInput::make('currency')
                    ->label('Currency Symbol')
                    ->default('$'),
            ])
            ->columns(2);
    }
}
```

## Step 2: Register the Service Provider

In Laravel 11+, service providers are automatically registered in `bootstrap/providers.php` when you run the `make:provider` command. If you're using an older version or need to manually register it, add your service provider to the `providers` array in `config/app.php`:

```php
'providers' => [
    // ... other providers
    App\Providers\ContentBlocksServiceProvider::class,
],
```

For Laravel 11+, your provider should already be registered in `bootstrap/providers.php`:

```php
<?php

return [
    // ... other providers
    App\Providers\ContentBlocksServiceProvider::class,
];
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details on how to contribute to this project.

## Security

If you discover any security vulnerabilities, please email security@yourproject.com instead of using the issue tracker.

## Credits

- [Henry](https://github.com/littleboy130491)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- 📖 [Documentation](https://github.com/littleboy130491/cms/wiki)
- 🐛 [Issue Tracker](https://github.com/littleboy130491/cms/issues)
- 💬 [Discussions](https://github.com/littleboy130491/cms/discussions)

---

Built with ❤️ using Laravel, Filament, and modern PHP practices.