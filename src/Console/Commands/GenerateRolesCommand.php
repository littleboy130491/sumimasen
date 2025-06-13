<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GenerateRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:generate-roles {--force : Force overwrite existing roles without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CMS roles: super admin, admin, and editor with appropriate permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Generating CMS roles...');

        // First, generate all Shield permissions
        $this->info('🔧 Generating Shield permissions for all resources...');
        try {
            $this->call('shield:generate', ['--all' => true, '--minimal' => true]);
            $this->info('✅ Shield permissions generated successfully');
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not generate Shield permissions: '.$e->getMessage());
        }

        // Try to get all available permissions
        try {
            $allPermissions = Permission::pluck('name')->toArray();
        } catch (\Exception $e) {
            $this->warn('⚠️  Database not accessible, generating with standard CMS permissions...');
            $allPermissions = $this->getDefaultCmsPermissions();
        }

        if (empty($allPermissions)) {
            $this->error('❌ No permissions found. Please ensure permissions are seeded first.');

            return 1;
        }

        $this->info('📊 Found {'.count($allPermissions).'} permissions');

        // Debug: Show backup-related permissions
        $backupPermissions = collect($allPermissions)->filter(function ($permission) {
            $permissionLower = strtolower($permission);

            return str_contains($permissionLower, 'backup') || str_contains($permissionLower, 'Backup');
        });

        if ($backupPermissions->isNotEmpty()) {
            $this->info('💾 Backup permissions found: '.$backupPermissions->implode(', '));
        } else {
            $this->info('💾 No backup permissions found');
        }

        $roles = $this->defineRoles($allPermissions);

        foreach ($roles as $roleName => $permissions) {
            // Debug: Show backup permissions for editor role
            if ($roleName === 'editor') {
                $editorBackupPermissions = collect($permissions)->filter(function ($permission) {
                    $permissionLower = strtolower($permission);

                    return str_contains($permissionLower, 'backup');
                });

                if ($editorBackupPermissions->isNotEmpty()) {
                    $this->warn('⚠️  Editor still has backup permissions: '.$editorBackupPermissions->implode(', '));
                } else {
                    $this->info('✅ Editor correctly has no backup permissions');
                }
            }

            $this->createOrUpdateRole($roleName, $permissions);
        }

        $this->info('✅ All roles have been generated successfully!');

        return 0;
    }

    /**
     * Define roles and their permissions
     */
    private function defineRoles(array $allPermissions): array
    {
        return [
            'super_admin' => $this->getSuperAdminPermissions($allPermissions),
            'admin' => $this->getAdminPermissions($allPermissions),
            'editor' => $this->getEditorPermissions($allPermissions),
        ];
    }

    /**
     * Super Admin gets all permissions (highest level)
     */
    private function getSuperAdminPermissions(array $allPermissions): array
    {
        return $allPermissions;
    }

    /**
     * Admin gets all permissions except super admin specific ones
     */
    private function getAdminPermissions(array $allPermissions): array
    {
        // Admin gets all permissions - same as current admin role in seeder
        return $allPermissions;
    }

    /**
     * Editor gets limited permissions (no user/role management, but includes components)
     */
    private function getEditorPermissions(array $allPermissions): array
    {
        return collect($allPermissions)->filter(function ($permission) {
            $permissionLower = strtolower($permission);

            // Define patterns for User related permissions
            $userPermissionPatterns = [
                'view_any_user',
                'view_user',
                'create_user',
                'update_user',
                'delete_user',
                'restore_user',
                'force_delete_user',
                'delete_any_user',
                'force_delete_any_user',
                'restore_any_user',
                'replicate_user',
                'reorder_user',
                'userresource::', // Covers Littleboy130491\Sumimasen\Filament\Resources\UserResource::*
            ];

            $rolePermissionPatterns = [
                'view_any_role',
                'view_role',
                'create_role',
                'update_role',
                'delete_role',
                'restore_role',
                'force_delete_role',
                'delete_any_role',
                'force_delete_any_role',
                'restore_any_role',
                'replicate_role',
                'reorder_role',
                'roleresource::', // Covers Littleboy130491\Sumimasen\Filament\Resources\RoleResource::*
                'shieldroleresource::', // Covers BezhanSalleh\FilamentShield\Resources\RoleResource::*
                'shield::role', // Covers BezhanSalleh\FilamentShield\Pages\ViewShieldSettings
            ];

            $backupPermissionPatterns = [
                'backup',           // Catches any permission containing 'backup'
                'Backup',           // Case sensitive backup
                'laravel-backup',   // Spatie Laravel Backup package
                'spatie-backup',    // Alternative naming
                'filament-spatie-backup', // Filament plugin naming
            ];

            // Check if permission matches user patterns
            $isUserPermission = collect($userPermissionPatterns)->some(function ($pattern) use ($permissionLower) {
                return Str::contains($permissionLower, $pattern);
            });

            // Check if permission matches role patterns
            $isRolePermission = collect($rolePermissionPatterns)->some(function ($pattern) use ($permissionLower) {
                return Str::contains($permissionLower, $pattern);
            });

            // Check if permission matches backup patterns
            $isBackupPermission = collect($backupPermissionPatterns)->some(function ($pattern) use ($permissionLower) {
                return Str::contains($permissionLower, $pattern);
            });

            // Include permission if it's not user, role, or backup related (this includes components)
            return ! $isUserPermission && ! $isRolePermission && ! $isBackupPermission;
        })->toArray();
    }

    /**
     * Get default CMS permissions (fallback when database is not accessible)
     */
    private function getDefaultCmsPermissions(): array
    {
        $resources = ['category', 'comment', 'component', 'page', 'post', 'submission', 'tag', 'user', 'role'];
        $actions = ['view', 'view_any', 'create', 'update', 'delete', 'restore', 'force_delete', 'delete_any', 'force_delete_any', 'restore_any', 'replicate', 'reorder'];

        $permissions = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = "{$action}_{$resource}";
            }
        }

        // Add backup permissions
        $permissions[] = 'view_backup';
        $permissions[] = 'create_backup';
        $permissions[] = 'delete_backup';
        $permissions[] = 'download_backup';

        // Add Shield permissions
        $permissions[] = 'shield::role';
        $permissions[] = 'shieldroleresource::view_any';
        $permissions[] = 'shieldroleresource::view';
        $permissions[] = 'shieldroleresource::create';
        $permissions[] = 'shieldroleresource::update';
        $permissions[] = 'shieldroleresource::delete';

        return $permissions;
    }

    /**
     * Create or update a role with permissions
     */
    private function createOrUpdateRole(string $roleName, array $permissions): void
    {
        $role = Role::where('name', $roleName)->first();

        if ($role) {
            $shouldOverwrite = $this->option('force') ||
                $this->confirm("Role '{$roleName}' already exists. Do you want to overwrite it?", true);

            if ($shouldOverwrite) {
                $role->syncPermissions($permissions);
                $this->info("✅ Role '{$roleName}' permissions updated ({$role->permissions->count()} permissions)");
            } else {
                $this->warn("⏭️  Skipping role '{$roleName}'");
            }
        } else {
            $role = Role::create(['name' => $roleName]);
            $role->givePermissionTo($permissions);
            $this->info("✅ Role '{$roleName}' created with {$role->permissions->count()} permissions");
        }
    }
}
