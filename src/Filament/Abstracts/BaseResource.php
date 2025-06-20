<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use CodeZero\UniqueTranslation\UniqueTranslationRule as UTR;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Filament\Forms\Components\SeoFields;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

abstract class BaseResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                ...static::formSchema(),
                ...static::formCustomFields(),
                ...static::formSeoSection(),
            ])
            ->columns(1); // Main form now has 1 column as Translate takes full width
    }

    protected static function formSchema(): array
    {

        return [
            Grid::make()
                ->columns([
                    'sm' => 3,
                    'xl' => 4,
                    '2xl' => 4,
                ])
                ->schema([
                    Translate::make()
                        ->columnSpanFull()
                        ->schema(function (string $locale): array {
                            return [
                                ...static::formTitleSlugFields($locale),
                                ...static::formContentFields($locale),
                                ...static::formSectionField($locale),
                            ];
                        })
                        ->columnSpan([
                            'sm' => 2,
                            'xl' => 3,
                            '2xl' => 3,
                        ]),
                    // Section for non-translatable fields and relationships
                    Section::make()
                        ->schema([
                            ...static::formFeaturedImageField(),
                            ...static::formRelationshipsFields(),
                            ...static::formAuthorRelationshipField(),
                            ...static::formStatusField(),
                            ...static::formTemplateField(),
                            ...static::formFeaturedField(),
                            ...static::formPublishedDateField(),
                            ...static::formMenuOrderField(),

                        ])
                        ->columnSpan([
                            'sm' => 1,
                            'xl' => 1,
                            '2xl' => 1,
                        ]),
                ]),

        ];

    }

    protected static function modelStatusOptions(): array
    {
        return ContentStatus::class::all();
    }

    protected static function formTitleSlugFields(string $locale, string $tableName = ''): array
    {
        $defaultLocale = config('cms.default_language', 'en'); // Default fallback

        if ($tableName === '') {
            $tableName = app(static::$model)->getTable();
        }

        return [
            TextInput::make('title')
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) use ($locale) {

                    if ($operation === 'edit' && ! empty($get('slug.'.$locale))) {
                        return;
                    }

                    $set('slug.'.$locale, $state ? Str::slug($state) : null);
                })
                ->required($locale === $defaultLocale),
            TextInput::make('slug')
                ->maxLength(255)
                ->rules(function (Get $get) use ($tableName): array {

                    return [
                        UTR::for($tableName, 'slug')
                            ->ignore($get('id')),
                        'alpha_dash',
                    ];
                })
                ->required($locale === $defaultLocale),
        ];
    }

    protected static function formContentFields(string $locale): array
    {

        return [];
    }

    protected static function formSectionField(string $locale): array
    {

        return [];
    }

    protected static function formCustomFields(): array
    {
        return [
            Section::make()
                ->schema([
                    KeyValue::make('custom_fields')
                        ->nullable(),
                ])
                ->columns(1),
        ];
    }

    protected static function formFeaturedImageField(): array
    {
        return [
            CuratorPicker::make('featured_image')
                ->relationship('featuredImage', 'id')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']),
        ];
    }

    protected static function formRelationshipsFields(): array
    {
        return [];
    }

    protected static function formTaxonomyRelationshipField(string $taxonomy): array
    {
        return [
            Select::make($taxonomy)
                ->relationship($taxonomy, 'title')
                ->multiple()
                ->searchable()
                ->preload()
                ->createOptionForm([
                    Translate::make()
                        ->columnSpanFull()
                        ->schema(function (string $locale) use ($taxonomy): array {
                            return [
                                ...static::formTitleSlugFields($locale, $taxonomy),
                            ];
                        }),
                ]),
        ];
    }

    protected static function formParentRelationshipField(): array
    {
        return [
            Select::make('parent_id')
                ->relationship('parent', 'title', ignoreRecord: true),
        ];
    }

    protected static function formAuthorRelationshipField(): array
    {
        return [
            Select::make('author_id')
                ->relationship('author', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->default(fn () => auth()->id()),
        ];
    }

    protected static function formStatusField(): array
    {
        return [
            Select::make('status')
                ->enum(ContentStatus::class)
                ->options(ContentStatus::class)
                ->default(ContentStatus::Draft)
                ->required(),
        ];
    }

    protected static function formTemplateField(): array
    {
        $subPath = '';

        return static::getTemplateOptions($subPath);
    }

    /**
     * Constructs the Filament Select component for templates.
     *
     * @param  string  $subPath  The subdirectory within 'resources/views/templates/'.
     * @return array An array containing the configured Filament Select component.
     */
    protected static function getTemplateOptions(string $subPath = ''): array
    {
        return [
            Select::make('template')
                ->options(function () use ($subPath) {
                    // Dynamically fetch file-based options when the field is rendered
                    return static::fetchRawTemplateData($subPath);
                })
                ->nullable()
                ->placeholder('Default System Template')
                ->default(null)
                ->label('Template')
                ->dehydrateStateUsing(function ($state) use ($subPath) {
                    // $state is the value of the 'template' field just before saving.

                    // If the state is already null (meaning "Default System Template" was selected
                    // or it was already null), keep it as null.
                    if ($state === null) {
                        return null;
                    }

                    // Fetch the current list of valid *file-based* template keys.
                    $currentFileOptions = static::fetchRawTemplateData($subPath);
                    $validFileOptionKeys = array_keys($currentFileOptions);

                    // Check if the current state is a valid file-based template.
                    if (in_array($state, $validFileOptionKeys, true)) {
                        return $state;
                    } else {
                        // If the state is not a valid file-based template, set it to null.
                        return null;
                    }
                }),
        ];
    }

    /**
     * Fetches only file-based template options.
     *
     * @param  string  $subPath  The subdirectory within 'resources/views/templates/'.
     * @return array An array of [filename => label] for file-based templates.
     */
    protected static function fetchRawTemplateData(string $subPath = ''): array
    {
        $options = [];
        $fullPath = 'views/templates/'.($subPath ? ltrim($subPath, '/') : '');
        $templatesPath = resource_path(rtrim($fullPath, '/'));

        if (File::isDirectory($templatesPath)) {
            $files = File::files($templatesPath);
            foreach ($files as $file) {
                $filename = $file->getFilenameWithoutExtension();
                // Ensure filename is not empty and use it as both key and value
                if (! empty($filename)) {
                    $options[$filename] = $filename;
                }
            }
        }

        return $options;
    }

    protected static function formFeaturedField(): array
    {

        return [
            Toggle::make('featured')
                ->default(false),
        ];
    }

    protected static function formPublishedDateField(): array
    {

        return [
            DateTimePicker::make('published_at')
                ->nullable(),
        ];
    }

    protected static function formMenuOrderField(): array
    {

        return [
            TextInput::make('menu_order') // Common menu order field
                ->numeric()
                ->default(0),
        ];
    }

    protected static function formSeoSection(): array
    {

        return [
            Section::make('SEO Settings')
                ->schema([
                    SeoFields::make(),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...static::tableColumns(),
            ])
            ->filters([
                ...static::tableFilters(),
            ])
            ->actions([
                ...static::tableActions(),
            ])
            ->bulkActions([
                ...static::tableBulkActions(),
            ])
            ->headerActions(
                [
                    ...static::tableHeaderActions(),
                ]
            )
            ->reorderable('menu_order')
            ->defaultSort('created_at', 'desc');

    }

    protected static function tableColumns(): array
    {

        return [
            TextColumn::make('title')
                ->searchable(['title', 'content'])
                ->sortable()
                ->limit(50),
            TextColumn::make('slug')
                ->limit(50),
            ...static::tableFeaturedColumn(),
            ...static::tableStatusColumn(),
            ...static::tableAuthorColumn(),
            ...static::tableDateColumns(),
            TextColumn::make('menu_order')
                ->label('Order')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function tableFeaturedColumn(): array
    {
        return [
            ToggleColumn::make('featured'),
        ];
    }

    protected static function tableStatusColumn(): array
    {
        return [
            TextColumn::make('status')
                ->badge()
                ->sortable(),
        ];
    }

    protected static function tableAuthorColumn(): array
    {
        return [
            TextColumn::make('author.name')
                ->sortable()
                ->searchable(),
        ];
    }

    protected static function tableDateColumns(): array
    {
        return [
            ...static::tablePublishedAtColumn(),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('deleted_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function tablePublishedAtColumn(): array
    {
        return [
            TextColumn::make('published_at')
                ->dateTime()
                ->sortable(),
        ];
    }

    protected static function tableFilters(): array
    {

        return [
            Tables\Filters\TrashedFilter::make(),
        ];
    }

    protected static function tableActions(): array
    {
        return [
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('replicate')
                ->icon('heroicon-o-document-duplicate')
                ->action(function (\Filament\Tables\Actions\Action $action, \Illuminate\Database\Eloquent\Model $record, \Livewire\Component $livewire) {
                    $newRecord = $record->replicate();

                    // Handle multilingual slug uniqueness
                    $originalSlugs = $newRecord->getTranslations('slug');
                    $newSlugs = [];
                    $locales = array_keys(config('cms.language_available')); // Get locales from app config

                    foreach ($locales as $locale) {

                        $originalSlug = Arr::get($originalSlugs, $locale);
                        if ($originalSlug) {
                            $count = 1;
                            $newSlug = $originalSlug;
                            // Check for uniqueness across all translations of the slug field
                            $modelClass = static::getModel();
                            while ($modelClass::whereJsonContains('slug->'.$locale, $newSlug)->exists()) {
                                $newSlug = $originalSlug.'-copy-'.$count++;
                            }
                            $newSlugs[$locale] = $newSlug;
                        } else {
                            $newSlugs[$locale] = null; // Or handle as needed for missing translations
                        }
                    }
                    $newRecord->setTranslations('slug', $newSlugs);

                    // Check if the record has a 'status' attribute and set it to 'Draft'
                    if (array_key_exists('status', $newRecord->getAttributes()) || $newRecord->isFillable('status')) {
                        $newRecord->status = ContentStatus::Draft;
                    }

                    // Check if the record has a 'published_at' attribute and set it to null
                    if (array_key_exists('published_at', $newRecord->getAttributes()) || $newRecord->isFillable('published_at')) {
                        $newRecord->published_at = null;
                    }

                    $newRecord->save();

                    $livewire->redirect(static::getUrl('index', ['record' => $newRecord]));
                }),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\ForceDeleteAction::make(),
            Tables\Actions\RestoreAction::make(),
        ];
    }

    protected static function tableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                ...static::tableEditBulkAction(),
                ...static::tableExportBulkAction(),
            ]),
        ];
    }

    protected static function tableEditBulkAction(): array
    {
        return [
            Tables\Actions\BulkAction::make('edit')
                ->form([
                    Select::make('status')
                        ->enum(ContentStatus::class)
                        ->options(ContentStatus::class)
                        ->nullable(),
                    Select::make('author_id')
                        ->relationship('author', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    DateTimePicker::make('published_at')
                        ->nullable(),
                ])
                ->action(function (\Illuminate\Support\Collection $records, array $data) {
                    $records->each(function (\Illuminate\Database\Eloquent\Model $record) use ($data) {
                        $updateData = [];
                        if (isset($data['status'])) {
                            $updateData['status'] = $data['status'];
                        }
                        if (isset($data['author_id'])) {
                            $updateData['author_id'] = $data['author_id'];
                        }
                        if (isset($data['published_at'])) {
                            $updateData['published_at'] = $data['published_at'];
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

    protected static function tableExportBulkAction(): array
    {
        return [];
    }

    protected static function tableHeaderActions(): array
    {
        return [];

    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
