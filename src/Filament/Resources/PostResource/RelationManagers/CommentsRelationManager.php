<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\PostResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Littleboy130491\Sumimasen\Enums\CommentStatus;
use Littleboy130491\Sumimasen\Filament\Traits\CommentTrait;

class CommentsRelationManager extends RelationManager
{
    use CommentTrait;

    protected static string $relationship = 'comments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...self::formSchema(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                ...static::tableColumns(),
            ])
            ->filters([
                //
            ])
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
                    ...self::tableEditBulkAction(),
                ]),
            ])
            ->emptyStateHeading('No comments for this record')
            ->emptyStateDescription('');

    }

}
