<?php

namespace Littleboy130491\Sumimasen;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Littleboy130491\Sumimasen\Filament\Pages\ManageGeneralSettings;
use Littleboy130491\Sumimasen\Filament\Resources\CategoryResource;
use Littleboy130491\Sumimasen\Filament\Resources\CommentResource;
use Littleboy130491\Sumimasen\Filament\Resources\ComponentResource;
use Littleboy130491\Sumimasen\Filament\Resources\PageResource;
use Littleboy130491\Sumimasen\Filament\Resources\PostResource;
use Littleboy130491\Sumimasen\Filament\Resources\SubmissionResource;
use Littleboy130491\Sumimasen\Filament\Resources\TagResource;
use Littleboy130491\Sumimasen\Filament\Resources\UserResource;

class SumimasenPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool $hasSettingsPage = true;
    
    protected array $resources = [];
    
    protected array $pages = [];

    public function getId(): string
    {
        return 'sumimasen-cms';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources($this->getResources())
            ->pages($this->getPages());
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function resources(array $resources): static
    {
        $this->resources = $resources;

        return $this;
    }

    public function getResources(): array
    {
        return array_merge([
            CategoryResource::class,
            CommentResource::class,
            ComponentResource::class,
            PageResource::class,
            PostResource::class,
            SubmissionResource::class,
            TagResource::class,
            UserResource::class,
        ], $this->resources);
    }

    public function pages(array $pages): static
    {
        $this->pages = $pages;

        return $this;
    }

    public function getPages(): array
    {
        $pages = [];

        if ($this->hasSettingsPage) {
            $pages[] = ManageGeneralSettings::class;
        }

        return array_merge($pages, $this->pages);
    }

    public function settingsPage(bool $condition = true): static
    {
        $this->hasSettingsPage = $condition;

        return $this;
    }

    public function hasSettingsPage(): bool
    {
        return $this->hasSettingsPage;
    }
}