<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Littleboy130491\Sumimasen\Filament\Exports\SubmissionExporter;
use Littleboy130491\Sumimasen\Filament\Resources\SubmissionResource\Pages;

class SubmissionResource extends Resource
{
    protected static ?string $model = null;

    public static function getModel(): string
    {
        return static::$model ??= class_exists(\App\Models\Submission::class)
            ? \App\Models\Submission::class
            : \Littleboy130491\Sumimasen\Models\Submission::class;
    }

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    public static function form(Form $form): Form
    {
        // Get existing JSON keys (from the model if editing, or a default structure)
        $record = $form->getModelInstance();
        $fields = $record?->fields ?? [
            'name' => '',
            'phone' => '',
            'email' => '',
            'message' => '',
        ];

        $components = [];
        foreach ($fields as $key => $value) {
            if ($key === 'message') {
                $components[] = Forms\Components\Textarea::make("fields.$key")->required();
            } else {
                $components[] = Forms\Components\TextInput::make("fields.$key")->required();
            }
        }

        return $form->schema($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fields.name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('fields.phone')
                    ->label('Phone'),
                Tables\Columns\TextColumn::make('fields.email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('fields.message')
                    ->label('Message'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(SubmissionExporter::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make()
                        ->exporter(SubmissionExporter::class),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSubmissions::route('/'),
            // 'create' => Pages\CreateSubmission::route('/create'),
            // 'edit' => Pages\EditSubmission::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
