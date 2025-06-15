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


use Awcodes\Curator\CuratorPlugin;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Illuminate\Validation\Rules\Password;
use Outerweb\FilamentTranslatableFields\Filament\Plugins\FilamentTranslatableFieldsPlugin;
use Filament\Navigation\NavigationGroup;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Illuminate\Support\Facades\Gate;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use Filament\Forms\Components\TextInput;

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
            ->pages($this->getPages())
            ->plugins([
                CuratorPlugin::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterNavigation: true,
                        navigationGroup: 'Users'
                    )
                    ->passwordUpdateRules(
                        rules: [Password::default()->mixedCase()->uncompromised(3)], // you may pass an array of validation rules as well. (default = ['min:8'])
                        requiresCurrentPassword: true, // when false, the user can update their password without entering their current password. (default = true)
                    ),
                FilamentTranslatableFieldsPlugin::make(),
                FilamentShieldPlugin::make(),
                FilamentMenuBuilderPlugin::make()
                    ->showCustomTextPanel()
                    ->addLocations($this->getMenuLocations())
                    ->addMenuItemFields([
                        TextInput::make('classes'),
                    ]),
                FilamentSpatieLaravelBackupPlugin::make()
                    ->authorize(fn(): bool => auth()->user()->hasRole(['admin', 'super_admin'])),
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Contents')
                    ->icon('heroicon-o-document-text'),
                NavigationGroup::make()
                    ->label('Users')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make()
                    ->label('Profile')
                    ->icon('heroicon-o-cog-6-tooth'),
                NavigationGroup::make()
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth'),
            ]);
    }

    public function boot(Panel $panel): void
    {
        $panel
            ->unsavedChangesAlerts()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications();
        Gate::policy(\Awcodes\Curator\Models\Media::class, \App\Policies\MediaPolicy::class);
        Gate::policy(\Datlechin\FilamentMenuBuilder\Models\Menu::class, \App\Policies\MenuPolicy::class);
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

    private function getMenuLocations(): array
    {
        $languages = config('cms.language_available', []);
        $locations = [];

        $baseLocations = config('cms.navigation_menu_locations', [
            'header' => 'Header',
            'footer' => 'Footer',
        ]);

        foreach ($baseLocations as $key => $label) {
            foreach ($languages as $langCode => $langName) {
                $locations["{$key}_{$langCode}"] = "{$label} ({$langName})";
            }
        }

        return $locations;
    }
}
