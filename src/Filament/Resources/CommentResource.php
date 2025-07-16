<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Littleboy130491\Sumimasen\Enums\CommentStatus;
use Littleboy130491\Sumimasen\Filament\Resources\CommentResource\Pages;
use Littleboy130491\Sumimasen\Filament\Traits\CommentTrait;

class CommentResource extends Resource
{
    use CommentTrait;

    protected static ?string $model = null;

    public static function getModel(): string
    {
        return static::$model ??= class_exists(\App\Models\Comment::class)
            ? \App\Models\Comment::class
            : \Littleboy130491\Sumimasen\Models\Comment::class;
    }

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getCommentFormSchema())
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->with(['commentable', 'parent']);
            })
            ->columns(self::getResourceTableColumns())
            ->filters(self::getCommentFilters())
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ...self::getBulkEditActions(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', CommentStatus::Pending)->count();
    }
}
