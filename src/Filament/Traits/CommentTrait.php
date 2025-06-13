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

        return config('cms.commentable_resources', []);

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
                modifyQueryUsing: fn (Builder $query) => $query->where('status', CommentStatus::Approved)
            )->label('Reply to'),
            ...static::formFieldsCommentable(),
        ];
    }

    public static function formFieldsCommentable(): array
    {
        return [
            TextInput::make('commentable_type')
                ->label('Resource Type')
                ->required()
                ->readOnly(),
            TextInput::make('commentable_id')
                ->label('Resource ID')
                ->required()
                ->readOnly(),
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
                ->sortable()
                ->searchable()
                ->url(function ($record): ?string {
                    $resources = self::getCommentableResources();
                    $resourceClass = $resources[$record->commentable_type] ?? null;
                    if (! $resourceClass) {
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
