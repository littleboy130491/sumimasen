<?php

namespace Littleboy130491\Sumimasen\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Littleboy130491\Sumimasen\Filament\Resources\ActivityLogResource\Pages;

class ActivityLogResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('timestamp')
                    ->label('Date & Time')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('activity')
                    ->label('Activity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'user_logged_in' => 'success',
                        'resource_created' => 'info',
                        'resource_updated' => 'warning',
                        'resource_deleted' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('user_name')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
                TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->searchable(),
                TextColumn::make('platform')
                    ->label('Platform')
                    ->searchable(),
                TextColumn::make('browser')
                    ->label('Browser')
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Resource Type')
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? class_basename($state) : 'N/A'
                    )
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity')
                    ->options([
                        'user_logged_in' => 'Login',
                        'resource_created' => 'Created',
                        'resource_updated' => 'Updated',
                        'resource_deleted' => 'Deleted',
                    ]),
                Tables\Filters\SelectFilter::make('device_type')
                    ->options([
                        'mobile' => 'Mobile',
                        'tablet' => 'Tablet',
                        'desktop' => 'Desktop',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalContent(function ($record) {
                        return view('sumimasen-cms::filament.activity-log-details', [
                            'record' => $record,
                        ]);
                    }),
            ])
            ->defaultSort('timestamp', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}