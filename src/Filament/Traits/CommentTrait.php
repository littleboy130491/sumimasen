<?php

namespace Littleboy130491\Sumimasen\Filament\Traits;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Littleboy130491\Sumimasen\Enums\CommentStatus;

trait CommentTrait
{
    public static function getCommentableResources(): array
    {
        // First, check for manually configured resources (backward compatibility)
        $configuredResources = config('cms.commentable_resources', []);

        // If manually configured, use those
        if (!empty($configuredResources)) {
            return $configuredResources;
        }

        // Auto-discover commentable resources
        return static::discoverCommentableResources();
    }

    protected static function discoverCommentableResources(): array
    {
        $resources = [];

        // Get all registered Filament resources
        $filamentResources = collect(app('filament')->getResources());

        foreach ($filamentResources as $resourceClass) {
            // Get the model class from the resource
            $modelClass = $resourceClass::getModel();

            // Check if the model uses HasComments trait
            if (static::modelHasCommentsTrait($modelClass)) {
                $resources[$modelClass] = $resourceClass;
            }
        }

        return $resources;
    }

    protected static function modelHasCommentsTrait(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $traits = class_uses_recursive($modelClass);

        return in_array('Littleboy130491\\Sumimasen\\Traits\\HasComments', $traits);
    }

    protected static function resolveResourceFromModel(string $modelClass): ?string
    {
        // Extract model name from full class path
        $modelName = class_basename($modelClass);

        // Standard naming convention: replace \Models\ with \Filament\Resources\ and append Resource
        $expectedResourceClass = str_replace('\\Models\\', '\\Filament\\Resources\\', $modelClass) . 'Resource';

        // Check if the resource exists
        if (class_exists($expectedResourceClass)) {
            return $expectedResourceClass;
        }

        // Fallback: look in the same namespace as the package
        $packageNamespace = 'Littleboy130491\\Sumimasen\\Filament\\Resources\\';
        $packageResourceClass = $packageNamespace . $modelName . 'Resource';

        if (class_exists($packageResourceClass)) {
            return $packageResourceClass;
        }

        return null;
    }

    public static function formSchema(): array
    {
        return [
            Textarea::make('content')->required()->maxLength(255)->columnSpan('full'),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')
                ->required()
                ->maxLength(255)
                ->email(),
            Select::make('status')->enum(CommentStatus::class)->options(CommentStatus::class)->default(CommentStatus::Pending)->required(),
            Select::make('parent_id')->relationship(
                name: 'parent',
                titleAttribute: 'id',
                ignoreRecord: true,
                modifyQueryUsing: fn(Builder $query) => $query->where('status', CommentStatus::Approved)
            )->label('Reply to'),
        ];
    }

    public static function tableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('content')
                ->limit(50)
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('name')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('email')
                ->sortable()
                ->searchable(),
            ...self::tableColumnsCommentable(),
            Tables\Columns\SelectColumn::make('status')->options(CommentStatus::class)
                ->sortable(),
            Tables\Columns\TextColumn::make('parent.id')
                ->label('Reply to')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('created_at')->sortable(),
        ];
    }

    public static function tableColumnsCommentable(): array
    {
        return [
            Tables\Columns\TextColumn::make('commentable_type')
                ->label('Type')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('commentable.id')
                ->label('Commentable ID')
                ->sortable()
                ->searchable()
                ->url(function ($record): ?string {
                    // Use the model's built-in method if available
                    if ($record->commentable && method_exists($record->commentable, 'getFilamentEditUrl')) {
                        return $record->commentable->getFilamentEditUrl();
                    }

                    // Fallback to traditional resource mapping
                    $resources = self::getCommentableResources();
                    $resourceClass = $resources[$record->commentable_type] ?? self::resolveResourceFromModel($record->commentable_type);

                    if (!$resourceClass || !class_exists($resourceClass)) {
                        return null;
                    }

                    return $resourceClass::getUrl('edit', ['record' => $record->commentable]);
                }),
        ];
    }

    public static function tableEditBulkAction(): array
    {
        return [
            Tables\Actions\BulkAction::make('edit')
                ->form([
                    Select::make('status')
                        ->enum(CommentStatus::class)
                        ->options(CommentStatus::class)
                        ->nullable(),
                ])
                ->action(function (\Illuminate\Support\Collection $records, array $data) {
                    $records->each(function (\Illuminate\Database\Eloquent\Model $record) use ($data) {
                        $updateData = [];
                        if (isset($data['status'])) {
                            $updateData['status'] = $data['status'];
                        }
                        $record->update($updateData);
                    });
                })
                ->deselectRecordsAfterCompletion()
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->label('Edit selected'),
        ];
    }
}
