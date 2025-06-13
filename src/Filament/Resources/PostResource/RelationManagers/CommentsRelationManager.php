<?php

namespace Littleboy130491\Sumimasen\Filament\Resources\PostResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
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

    public static function formFieldsCommentable(): array
    {
        return [];
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
        ;
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
            Tables\Columns\SelectColumn::make('status')->options(CommentStatus::class)
                ->sortable(),
            Tables\Columns\TextColumn::make('parent.id')
                ->label('Reply to')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('created_at')->sortable(),
        ];
    }

}
