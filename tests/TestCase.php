<?php

namespace Littleboy130491\Sumimasen\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Littleboy130491\Sumimasen\SumimasenServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Littleboy130491\\Sumimasen\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->afterApplicationCreated(function () {
            $this->createCmsTableSchemas();
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            \Spatie\Permission\PermissionServiceProvider::class,
            \Spatie\ResponseCache\ResponseCacheServiceProvider::class,
            SumimasenServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure spatie/laravel-permission
        config()->set('permission.models.permission', \Spatie\Permission\Models\Permission::class);
        config()->set('permission.models.role', \Spatie\Permission\Models\Role::class);
        config()->set('permission.table_names.roles', 'roles');
        config()->set('permission.table_names.permissions', 'permissions');
        config()->set('permission.table_names.model_has_permissions', 'model_has_permissions');
        config()->set('permission.table_names.model_has_roles', 'model_has_roles');
        config()->set('permission.table_names.role_has_permissions', 'role_has_permissions');
        config()->set('permission.column_names.role_pivot_key', null);
        config()->set('permission.column_names.permission_pivot_key', null);
        config()->set('permission.column_names.model_morph_key', 'model_id');
        config()->set('permission.column_names.team_foreign_key', 'team_id');

        // Configure spatie/laravel-responsecache
        config()->set('responsecache.enabled', false); // Disable in tests
        config()->set('responsecache.cache_lifetime_in_seconds', 60 * 60 * 24 * 7);
        config()->set('responsecache.cache_tag_prefix', 'responsecache');
        config()->set('responsecache.cache_key_prefix', 'responsecache');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();

        // Create basic Laravel tables that might be needed
        $this->artisan('migrate:fresh', [
            '--database' => 'testing',
        ]);

        // Create CMS-specific tables
        $this->createCmsTableSchemas();
    }

    private function createCmsTableSchemas()
    {
        // Create the basic tables needed for testing
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('pages', function ($table) {
            $table->id();
            $table->json('title');
            $table->json('slug')->nullable();
            $table->json('content')->nullable();
            $table->json('excerpt')->nullable();
            $table->json('section')->nullable();
            $table->string('template')->default('default');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('parent_id')->nullable()->constrained('pages');
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->json('custom_fields')->nullable();
            $table->integer('menu_order')->default(0);
            $table->string('featured_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('posts', function ($table) {
            $table->id();
            $table->json('title');
            $table->json('slug')->nullable();
            $table->json('content')->nullable();
            $table->json('excerpt')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->constrained('users');
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->json('custom_fields')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('featured_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('categories', function ($table) {
            $table->id();
            $table->json('title');
            $table->json('slug');
            $table->json('content')->nullable();
            $table->string('template')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories');
            $table->string('featured_image')->nullable();
            $table->integer('menu_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('tags', function ($table) {
            $table->id();
            $table->json('title');
            $table->json('slug');
            $table->json('content')->nullable();
            $table->string('template')->nullable();
            $table->string('featured_image')->nullable();
            $table->integer('menu_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('comments', function ($table) {
            $table->id();
            $table->text('content');
            $table->string('status')->default('pending');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('parent_id')->nullable()->constrained('comments');
            $table->morphs('commentable');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('submissions', function ($table) {
            $table->id();
            $table->json('fields');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('components', function ($table) {
            $table->id();
            $table->string('name');
            $table->json('data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('category_post', function ($table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('post_id')->constrained();
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('post_tag', function ($table) {
            $table->id();
            $table->foreignId('post_id')->constrained();
            $table->foreignId('tag_id')->constrained();
            $table->timestamps();
        });

        // Create spatie/laravel-permission tables
        $this->app['db']->connection()->getSchemaBuilder()->create('permissions', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('model_has_permissions', function ($table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type']);
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('model_has_roles', function ($table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type']);
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('role_has_permissions', function ($table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
        });
    }
}
