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
    /**
     * Form schema for standalone Comment resource.
     */
    public static function getCommentFormSchema(): array
    {
        return [
            Textarea::make('content')
                ->required()
                ->maxLength(1000)
                ->columnSpan('full'),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->required()
                ->maxLength(255)
                ->email(),
            Select::make('status')
                ->enum(CommentStatus::class)
                ->options(CommentStatus::class)
                ->default(CommentStatus::Pending)
                ->required(),
            Select::make('parent_id')
                ->relationship(
                    name: 'parent',
                    titleAttribute: 'id',
                    ignoreRecord: true,
                    modifyQueryUsing: fn (Builder $query) => $query->where('status', CommentStatus::Approved)
                )
                ->label('Reply to'),
        ];
    }

    /**
     * Form schema for relation manager (no commentable fields needed).
     */
    public static function getRelationManagerFormSchema(): array
    {
        return [
            Textarea::make('content')
                ->required()
                ->maxLength(1000)
                ->columnSpan('full'),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->required()
                ->maxLength(255)
                ->email(),
            Select::make('status')
                ->enum(CommentStatus::class)
                ->options(CommentStatus::class)
                ->default(CommentStatus::Pending)
                ->required(),
            Select::make('parent_id')
                ->relationship(
                    name: 'parent',
                    titleAttribute: 'id',
                    ignoreRecord: true,
                    modifyQueryUsing: function (Builder $query, $livewire) {
                        // Only show parent comments for the same commentable
                        return $query->where('status', CommentStatus::Approved)
                            ->where('commentable_type', $livewire->getOwnerRecord()::class)
                            ->where('commentable_id', $livewire->getOwnerRecord()->getKey());
                    }
                )
                ->label('Reply to'),
        ];
    }

    /**
     * Base table columns (common to both contexts).
     */
    public static function getBaseTableColumns(): array
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
            Tables\Columns\SelectColumn::make('status')
                ->options(CommentStatus::class)
                ->sortable(),
            Tables\Columns\TextColumn::make('parent.id')
                ->label('Reply to')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('created_at')
                ->sortable(),
        ];
    }

    /**
     * Table columns for standalone Comment resource (includes commentable info).
     */
    public static function getResourceTableColumns(): array
    {
        return [
            ...self::getBaseTableColumns(),
            Tables\Columns\TextColumn::make('commentable_type')
                ->label('Type')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('commentable.id')
                ->label('Commentable ID')
                ->sortable()
                ->searchable()
                ->url(function ($record): ?string {
                    if ($record->commentable && method_exists($record->commentable, 'getFilamentEditUrl')) {
                        return $record->commentable->getFilamentEditUrl();
                    }

                    return null;
                }),
        ];
    }

    /**
     * Table columns for relation manager (no commentable info needed).
     */
    public static function getRelationManagerTableColumns(): array
    {
        return self::getBaseTableColumns();
    }

    /**
     * Bulk actions for editing comment status.
     */
    public static function getBulkEditActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('edit_status')
                ->label('Update Status')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->form([
                    Select::make('status')
                        ->enum(CommentStatus::class)
                        ->options(CommentStatus::class)
                        ->required(),
                ])
                ->action(function (\Illuminate\Support\Collection $records, array $data) {
                    $records->each(function ($record) use ($data) {
                        $record->update(['status' => $data['status']]);
                    });
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }

    /**
     * Get table filters for comments.
     */
    public static function getCommentFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->options(CommentStatus::class),
        ];
    }
}
