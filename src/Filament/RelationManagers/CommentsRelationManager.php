<?php

namespace Littleboy130491\Sumimasen\Filament\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Littleboy130491\Sumimasen\Filament\Traits\CommentTrait;

class CommentsRelationManager extends RelationManager
{
    use CommentTrait;

    protected static string $relationship = 'comments';

    public function form(Form $form): Form
    {
        return $form
            ->schema(self::getRelationManagerFormSchema())
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->modifyQueryUsing(function ($query) {
                return $query->with(['parent']); // Prevent N+1 queries
            })
            ->columns(self::getRelationManagerTableColumns())
            ->filters(self::getCommentFilters())
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
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
            ->emptyStateHeading('No comments yet')
            ->emptyStateDescription('Comments will appear here when added')
            ->defaultSort('created_at', 'desc');
    }
}
