<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Filament\Forms\Components\Builder as FormsBuilder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Littleboy130491\Sumimasen\Filament\Resources\ComponentResource\Pages;
use Littleboy130491\Sumimasen\Filament\Traits\HasContentBlocks;
use Littleboy130491\Sumimasen\Filament\Traits\HasCopyFromDefaultLangButton;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class ComponentResource extends Resource
{
    use HasContentBlocks, HasCopyFromDefaultLangButton;

    protected static ?string $model = null;

    public static function getModel(): string
    {
        return static::$model ??= class_exists(\App\Models\Component::class)
            ? \App\Models\Component::class
            : \Littleboy130491\Sumimasen\Models\Component::class;
    }

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Patterns';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/i') // basic slug regex
                    ->unique(ignoreRecord: true)
                    ->helperText('Only lowercase letters, numbers, and hyphens are allowed. No spaces or special characters.'),
                Textarea::make('notes')
                    ->helperText('Notes for this component. For informational purposes only.'),
                Translate::make()
                    ->columnSpanFull()
                    ->schema(function (string $locale): array {
                        return [
                            FormsBuilder::make('section')
                                ->collapsed(false)
                                ->cloneable()
                                ->blocks(static::getContentBlocks()),
                        ];
                    })
                    ->actions([
                        static::copyFromDefaultLangAction(),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComponents::route('/'),
            'create' => Pages\CreateComponent::route('/create'),
            'edit' => Pages\EditComponent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
