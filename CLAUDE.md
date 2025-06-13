# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Package Development
- `composer test` - Run the test suite using Pest
- `composer format` - Format code using Laravel Pint
- `composer analyse` - Run static analysis with PHPStan
- `composer test-coverage` - Run tests with coverage report

### Laravel Package Tools
- `php artisan vendor:publish --tag="cms-migrations"` - Publish migrations
- `php artisan vendor:publish --tag="cms-config"` - Publish config file
- `php artisan vendor:publish --tag="cms-views"` - Publish views

### Custom Artisan Commands
The package provides several custom commands:
- `php artisan cms:create-exporter` - Create new exporter class
- `php artisan cms:create-importer` - Create new importer class  
- `php artisan cms:create-migration` - Create CMS migration
- `php artisan cms:create-model` - Create CMS model
- `php artisan cms:generate-roles` - Generate permission roles
- `php artisan cms:generate-sitemap` - Generate XML sitemap
- `php artisan cms:publish-scheduled-content` - Publish scheduled content
- `php artisan cms:refresh-instagram-token` - Refresh Instagram API token
- `php artisan cms:sync-curator-media` - Sync Curator media files

## Architecture Overview

### Package Structure
This is a Laravel package named "littleboy130491/cms" - a Filament-based CMS system. The main namespace is `Littleboy130491\Sumimasen`.

### Core Components

**Service Provider**: `src/Providers/CmsServiceProvider.php` - Main entry point that registers:
- Filament resources (Pages, Posts, Categories, Tags, Comments, Users, etc.)
- Livewire components (LikeButton, SubmissionForm)
- Custom commands
- Views and migrations

**Content Models**: The CMS supports multiple content types defined in `config/cms.php`:
- **Pages**: Static content with hierarchical structure
- **Posts**: Blog-style content with categories/tags
- **Categories/Tags**: Taxonomies for organizing posts
- **Comments**: User-generated content system
- **Components**: Reusable content blocks

**Filament Integration**: 
- Uses abstract base classes (`src/Filament/Abstracts/`) for consistent resource structure
- All resources extend `BaseResource` which provides common form/table patterns
- Content resources extend `BaseContentResource` for shared content functionality

### Key Features

**Multilingual Support**: 
- Uses `spatie/laravel-translatable` package
- Configured languages: English, Indonesian, Chinese, Korean
- Content fields are translatable with form components wrapped in `Translate`

**Content Management**:
- Hierarchical pages with parent/child relationships
- Post categorization and tagging system
- SEO optimization using `Littleboy130491/seo-suite`
- Media management via `awcodes/curator`
- Content scheduling and status management

**Frontend Integration**:
- Blade view components for rendering content
- Livewire components for interactive elements
- Template system for different content layouts
- Navigation menu management

### Database Schema
All models use soft deletes and include:
- Content status enum (draft, published, scheduled, etc.)
- Multi-language fields
- SEO metadata
- Custom fields as JSON
- Relationship tables for taxonomies

### Testing Framework
- Uses Pest PHP for testing
- Includes architecture tests (`ArchTest.php`)
- Controller tests for content routing