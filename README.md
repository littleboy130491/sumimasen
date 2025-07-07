# Sumimasen CMS

A powerful, multilingual Laravel CMS package built with Filament v3. Features hierarchical content management, role-based permissions, dynamic components, and extensive customization options.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/littleboy130491/cms.svg?style=flat-square)](https://packagist.org/packages/littleboy130491/cms)
[![Total Downloads](https://img.shields.io/packagist/dt/littleboy130491/cms.svg?style=flat-square)](https://packagist.org/packages/littleboy130491/cms)

## Features

🌐 **Multilingual Content** - Full translation support with smart fallback and URL redirection  
📝 **Content Management** - Pages, posts, categories, tags, and comments with hierarchical structure  
🔐 **Role-Based Access** - Comprehensive permission system with predefined roles  
🧩 **Dynamic Components** - Flexible component system for reusable content blocks  
📊 **SEO Optimized** - Built-in SEO suite integration and sitemap generation  
📱 **Responsive Admin** - Beautiful Filament-powered admin interface  
🎨 **Template System** - WordPress-like template hierarchy for complete design control  
📧 **Form System** - Built-in contact forms with email notifications and reCAPTCHA  
📈 **Analytics** - Page views and likes tracking with real-time updates  
🔄 **Scheduled Publishing** - Automatic content publishing with cron integration  
📷 **Media Management** - Advanced media handling with Curator integration  
🎯 **Performance** - Optimized queries, caching, and queue support  
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

The CMS supports multiple content types defined in `config/cms.php`:

```php
'content_models' => [
    'pages' => [
        'model' => \Littleboy130491\Sumimasen\Models\Page::class,
        'route_prefix' => '',
        'translatable' => true,
    ],
    'posts' => [
        'model' => \Littleboy130491\Sumimasen\Models\Post::class,
        'route_prefix' => 'blog',
        'translatable' => true,
        'archive_view' => 'templates.archives.posts',
    ],
    // Add your custom content types here
],
```

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

### Template System

Create custom templates in `resources/views/templates/`:

```
templates/
├── default.blade.php           # Default template
├── home.blade.php             # Home page template
├── singles/
│   ├── page.blade.php         # All pages
│   ├── post.blade.php         # All posts
│   └── page-about.blade.php   # Specific page
└── archives/
    ├── archive.blade.php      # Default archive
    └── posts.blade.php        # Posts archive
```

## Available Commands

### Content Management
```bash
# Install CMS
php artisan cms:install

# Generate permission roles
php artisan cms:generate-roles

# Publish scheduled content
php artisan cms:publish-scheduled-content

# Generate sitemap
php artisan cms:generate-sitemap
```

### Development Tools
```bash
# Create new model
php artisan cms:create-model

# Create new migration
php artisan cms:create-migration

# Create new exporter
php artisan cms:create-exporter

# Create new importer
php artisan cms:create-importer
```

### Media & External Services
```bash
# Sync Curator media files
php artisan cms:sync-curator-media

# Refresh Instagram token
php artisan cms:refresh-instagram-token
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
<sumimasen-cms.livewire::like-button :content="$post" />

{{-- Contact form with reCAPTCHA --}}
<sumimasen-cms.livewire::submission-form />

{{-- Comment form (if implementing frontend comments) --}}
<sumimasen-cms.livewire::comment-form :commentable="$post" />
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

### Custom Comment Integration

Create custom comment forms and displays:

```php
// Custom comment resource
class CustomCommentResource extends CommentResource
{
    use CommentTrait;
    
    public static function form(Form $form): Form
    {
        return $form->schema(self::getCommentFormSchema());
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns(self::getResourceTableColumns());
    }
}
```

### Custom Templates

Create specialized templates:
- `page-{slug}.blade.php` - Specific pages
- `single-{type}.blade.php` - Content types
- `archive-{type}.blade.php` - Archive pages
- `taxonomy-{taxonomy}.blade.php` - Taxonomy archives

## Testing

Run the test suite:

```bash
composer test
```

Run specific test types:

```bash
composer test-coverage  # With coverage report
composer analyse        # Static analysis
composer format         # Code formatting
```

## Troubleshooting

### Common Issues

**Plugin not showing resources:**
- Ensure plugin is registered in panel provider
- Clear Filament cache: `php artisan filament:optimize-clear`

**Migrations failing:**
- Check database permissions
- Ensure all required packages are installed
- Run: `php artisan migrate:status`

**Assets not loading:**
- Publish assets: `php artisan vendor:publish --tag=cms-views`
- Clear view cache: `php artisan view:clear`

**reCAPTCHA not working:**
- Verify site and secret keys
- Check domain registration in Google Console
- Ensure HTTPS in production

**Comments not showing:**
- Ensure model uses `HasComments` trait
- Check comment status (must be approved to show)
- Verify relationship is properly loaded

### Debug Mode

Enable debug mode for detailed error information:

```env
CMS_DEBUG_MODE_ENABLED=true
APP_DEBUG=true
```

## API Reference

### Comment Model Methods

```php
// Relationship methods
$comment->commentable  // Get the parent model (post, page, etc.)
$comment->parent      // Get parent comment (for replies)
$comment->replies     // Get child comments

// Status methods
$comment->isApproved()
$comment->isPending()
$comment->isRejected()

// Utility methods
$comment->getDepth()           // Get nesting level
$comment->isReply()           // Check if it's a reply
$comment->hasReplies()        // Check if has replies
```

### HasComments Trait Methods

```php
// Relationship methods
$model->comments                    // All comments
$model->approvedComments           // Only approved comments

// Utility methods
$model->getFilamentResourceClass() // Get associated Filament resource
$model->getFilamentEditUrl()       // Get admin edit URL
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details on how to contribute to this project.

## Security

If you discover any security vulnerabilities, please email security@yourproject.com instead of using the issue tracker.

## Credits

- [Henry](https://github.com/littleboy130491)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- 📖 [Documentation](https://github.com/littleboy130491/cms/wiki)
- 🐛 [Issue Tracker](https://github.com/littleboy130491/cms/issues)
- 💬 [Discussions](https://github.com/littleboy130491/cms/discussions)

---

Built with ❤️ using Laravel, Filament, and modern PHP practices.