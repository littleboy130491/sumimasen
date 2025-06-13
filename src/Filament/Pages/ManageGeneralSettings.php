<?php

namespace Littleboy130491\Sumimasen\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use Littleboy130491\Sumimasen\Settings\GeneralSettings;

class ManageGeneralSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = GeneralSettings::class;

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'General Settings';

    protected static ?string $navigationLabel = 'General';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site Information')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('Site Name')
                            ->maxLength(255)
                            ->columnSpan('full'),

                        Forms\Components\Textarea::make('site_description')
                            ->label('Site Description')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpan('full'),

                        Forms\Components\TextInput::make('email')
                            ->label('Site Email')
                            ->email()
                            ->maxLength(255)
                            ->columnSpan('full'),

                        Forms\Components\TextInput::make('phone_1')
                            ->label('Phone 1')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('phone_2')
                            ->label('Phone 2')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('whatsapp_1')
                            ->label('WhatsApp 1')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('whatsapp_2')
                            ->label('WhatsApp 2')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->maxLength(255)
                            ->rows(2)
                            ->columnSpan('full'),
                    ])->columns(2),

                Forms\Components\Section::make('Site Assets')
                    ->schema([
                        Forms\Components\TextInput::make('site_logo')
                            ->label('Site Logo')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('site_favicon')
                            ->label('Site Favicon')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Social Media')
                    ->schema([
                        Forms\Components\TextInput::make('facebook')
                            ->label('Facebook')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('twitter')
                            ->label('Twitter')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('instagram')
                            ->label('Instagram')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('linkedin')
                            ->label('LinkedIn')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('youtube')
                            ->label('YouTube')
                            ->maxLength(255),
                    ])->columns(2),

            ]);
    }
}
