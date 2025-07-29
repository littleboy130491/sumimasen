<?php

namespace Littleboy130491\Sumimasen\Filament\Abstracts;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use CodeZero\UniqueTranslation\UniqueTranslationRule as UTR;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Builder as FormsBuilder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Filament\Forms\Components\SeoFields;
use Littleboy130491\Sumimasen\Filament\Traits\HasContentBlocks;
use Littleboy130491\Sumimasen\Filament\Traits\HasCopyFromDefaultLangButton;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

abstract class BaseResource extends Resource
{
    use HasContentBlocks,
        HasCopyFromDefaultLangButton;

    protected static ?string $recordTitleAttribute = 'title';

    protected static function isTranslatable(): bool
    {
        return config('cms.multilanguage_enabled', false);
    }

    protected static function hiddenFields(): array
    {
        return []; // required fields (ex:'title' and 'slug') should never be hidden
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::formSchema())
            ->columns(2);
    }

    protected static function formSchema(): array
    {
        $schema = [
            ...static::getTopSection(),
            ...static::getBottomSection(),
        ];

        return $schema;
    }

    protected static function getTopSection(): array
    {
        return [
            Grid::make()
                ->columns([
                    'sm' => 3,
                    'xl' => 4,
                    '2xl' => 4,
                ])
                ->schema([
                    // Top Left Section - Translated Fields
                    Section::make('Main Fields')
                        ->schema([
                            Translate::make()
                                ->columns(2)
                                ->schema(function (string $locale): array {
                                    return static::topLeftSchema($locale);
                                })
                                ->contained(false)
                                ->actions([
                                    static::copyFromDefaultLangAction(), // from trait HasCopyFromDefaultLangButton
                                ]),

                        ])
                        ->columnSpan([
                            'sm' => 2,
                            'xl' => 3,
                            '2xl' => 3,
                        ])
                        ->collapsible(),
                    // Top Right Section
                    Section::make('Settings')
                        ->schema(static::topRightSchema())
                        ->columnSpan([
                            'sm' => 1,
                            'xl' => 1,
                            '2xl' => 1,
                        ])
                        ->collapsible(),
                ]),
        ];
    }

    protected static function topLeftSchema(string $locale): array
    {
        $schema = [
            ...static::formTitleSlugFields($locale),
            ...static::formContentFields($locale),
            ...static::additionalTranslatableFormFields($locale), // hook for additional translatable fields
        ];

        return $schema;
    }

    protected static function topRightSchema(): array
    {
        $schema = [
            ...static::formFeaturedImageField(),
            ...static::formRelationshipsFields(),
            ...static::formAuthorRelationshipField(),
            ...static::formStatusField(),
            ...static::formTemplateField(),
            ...static::formFeaturedField(),
            ...static::formPublishedDateField(),
            ...static::formMenuOrderField(),
        ];

        return $schema;
    }

    protected static function getBottomSection(): array
    {
        $sections = [];

        // Hook Additional Non-Translatable Fields Section
        if (! empty(static::additionalNonTranslatableFormFields())) {
            $sections[] = Section::make('Additional Fields')
                ->schema(static::additionalNonTranslatableFormFields())
                ->columns(2);
        }

        // Custom Fields Section
        if (static::modelHasColumn('custom_fields') && ! static::isFieldHidden('custom_fields')) {
            $sections[] =
                Section::make('Custom Fields')
                    ->schema([
                        KeyValue::make('custom_fields')
                            ->nullable(),
                    ])
                    ->columns(1)
                    ->collapsible();
        }

        // SEO Section
        $sections[] =
            Section::make('SEO Settings')
                ->schema([
                    SeoFields::make(),
                ])
                ->collapsible();

        return $sections;

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
                ->columnSpanFull()
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) use ($locale) {

                    if ($operation === 'edit' && ! empty($get('slug.'.$locale))) {
                        return;
                    }

                    $set('slug.'.$locale, $state ? Str::slug($state) : null);
                })
                ->required($locale === $defaultLocale),
            TextInput::make('slug')
                ->columnSpanFull()
                ->maxLength(255)
                ->rules(function (Get $get) use ($tableName): array {

                    return [
                        UTR::for($tableName, 'slug')
                            ->ignore($get('id')),
                        'alpha_dash',
                    ];
                })
                ->required($locale === $defaultLocale)
                ->suffixAction(
                    Action::make('generate')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (Set $set, Get $get) use ($locale) {
                            $titleValue = $get('title.'.$locale);
                            if ($titleValue) {
                                $slug = Str::slug($titleValue);
                                $set('slug.'.$locale, $slug);
                            }
                        })
                ),
        ];
    }

    protected static function formContentFields(?string $locale): array
    {
        $fields = [];

        if (static::modelHasColumn('content') && ! static::isFieldHidden('content')) {
            $fields[] = TiptapEditor::make('content')
                ->profile('simple')
                ->nullable()
                ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                ->columnSpanFull();
        }

        if (static::modelHasColumn('excerpt') && ! static::isFieldHidden('excerpt')) {
            $fields[] = Textarea::make('excerpt')
                ->nullable()
                ->columnSpanFull();
        }

        if (static::modelHasColumn('section') && ! static::isFieldHidden('section')) {
            $fields[] = FormsBuilder::make('section')
                ->collapsed(false)
                ->blocks(static::getContentBlocks()) // from trait HasContentBlocks
                ->cloneable()
                ->columnSpanFull();
        }

        return $fields;
    }

    protected static function additionalTranslatableFormFields(?string $locale): array
    {

        return []; // hook for additional translatable fields
    }

    protected static function additionalNonTranslatableFormFields(): array
    {

        return []; // hook for additional non-translatable fields
    }

    protected static function formFeaturedImageField(): array
    {
        if (! static::modelHasColumn('featured_image') || static::isFieldHidden('featured_image')) {
            return [];
        }

        return [
            CuratorPicker::make('featured_image')
                ->relationship('featuredImage', 'id')
                ->preserveFilenames()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']),
        ];
    }

    protected static function formRelationshipsFields(): array
    {
        return []; // relationships are handled in the child class
    }

    protected static function formTaxonomyRelationshipField(string $relationship, ?string $tableName = '', bool $multiple = true): array
    {
        // Check if the relationship exists on the model
        if (! static::modelHasRelationship($relationship) || static::isFieldHidden($relationship)) {
            return [];
        }

        // if $tableName empty
        if (empty($tableName)) {
            // check whether there is table with the name $relationship
            if (Schema::hasTable($relationship)) {
                $tableName = $relationship;
                // check whether there is table with the name snake_case of $relationship
            } elseif (Schema::hasTable(Str::plural(Str::snake($relationship)))) {
                $tableName = Str::plural(Str::snake($relationship));
            } else {
                return [];
            }
        }

        $select = Select::make($relationship)
            ->relationship($relationship, 'title')
            ->multiple($multiple)
            ->searchable()
            ->preload();

        // Convert relationship name to Shield permission format
        $permissionName = 'create_'.Str::snake(Str::singular($relationship), '::');

        if (auth()->user()->can($permissionName)) {
            $select->createOptionForm([
                Translate::make()
                    ->columnSpanFull()
                    ->schema(function (string $locale) use ($tableName): array {
                        return [
                            ...static::formTitleSlugFields($locale, $tableName),
                        ];
                    }),
            ]);
        }

        return [$select];
    }

    protected static function formParentRelationshipField(): array
    {
        if (! static::modelHasColumn('parent_id') || static::isFieldHidden('parent_id')) {
            return [];
        }

        return [
            Select::make('parent_id')
                ->relationship('parent', 'title', ignoreRecord: true),
        ];
    }

    protected static function formAuthorRelationshipField(): array
    {
        if (! static::modelHasColumn('author_id') || static::isFieldHidden('author_id')) {
            return [];
        }

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
        if (! static::modelHasColumn('status') || static::isFieldHidden('status')) {
            return [];
        }

        return [
            Select::make('status')
                ->enum(ContentStatus::class)
                ->options(ContentStatus::class)
                ->default(ContentStatus::Draft)
                ->required(),
        ];
    }

    protected static function formTemplateField(string $subPath = ''): array
    {
        if (! static::modelHasColumn('template') || static::isFieldHidden('template')) {
            return [];
        }

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
        if (! static::modelHasColumn('featured') || static::isFieldHidden('featured')) {
            return [];
        }

        return [
            Toggle::make('featured')
                ->default(false),
        ];
    }

    protected static function formPublishedDateField(): array
    {
        if (! static::modelHasColumn('published_at') || static::isFieldHidden('published_at')) {
            return [];
        }

        return [
            DateTimePicker::make('published_at')
                ->nullable(),
        ];
    }

    protected static function formMenuOrderField(): array
    {
        if (! static::modelHasColumn('menu_order') || static::isFieldHidden('menu_order')) {
            return [];
        }

        return [
            TextInput::make('menu_order')
                ->numeric()
                ->default(0),
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
        $columns = [];

        if (static::modelHasColumn('title')) {
            $currentLocale = app()->getLocale();

            $columns[] = TextColumn::make('title')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    $currentLocale = app()->getLocale();

                    return $query->where(function (Builder $subQuery) use ($search, $currentLocale) {
                        // Search in title for current locale
                        $subQuery->whereRaw(
                            "LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$currentLocale}'))) LIKE LOWER(?)",
                            ["%{$search}%"]
                        );

                        // Also search in content if it exists
                        if (static::modelHasColumn('content')) {
                            $subQuery->orWhereRaw(
                                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(content, '$.{$currentLocale}'))) LIKE LOWER(?)",
                                ["%{$search}%"]
                            );
                        }

                        // Try Laravel's JSON path syntax as alternative
                        $subQuery->orWhere('title->'.$currentLocale, 'like', "%{$search}%");
                    });
                })
                ->sortable()
                ->limit(50)
                ->getStateUsing(function ($record) use ($currentLocale) {
                    return $record->getTranslation('title', $currentLocale);
                });
        }

        if (static::modelHasColumn('slug')) {
            $columns[] =
                TextColumn::make('slug')
                    ->limit(50);
        }

        if (static::modelHasColumn('featured') && ! static::isFieldHidden('featured')) {
            $columns[] =
                ToggleColumn::make('featured');
        }

        if (static::modelHasColumn('status') && ! static::isFieldHidden('status')) {
            $columns[] =
                TextColumn::make('status')
                    ->badge()
                    ->sortable();
        }

        if (static::modelHasRelationship('author') && ! static::isFieldHidden('author_id')) {
            $columns[] =
                TextColumn::make('author.name')
                    ->sortable()
                    ->searchable();
        }

        // hook for additional columns
        $columns = [...$columns, ...static::additionalTableColumns()];

        $columns = [...$columns, ...static::tableDateColumns()];

        if (static::modelHasColumn('menu_order') && ! static::isFieldHidden('menu_order')) {
            $columns[] =
                TextColumn::make('menu_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true);
        }

        return $columns;

    }

    protected static function tableDateColumns(): array
    {
        $columns = [];

        if (static::modelHasColumn('published_at') && ! static::isFieldHidden('published_at')) {
            $columns[] =
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable();
        }

        if (static::modelHasColumn('created_at') && ! static::isFieldHidden('created_at')) {
            $columns[] =
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::modelHasColumn('updated_at') && ! static::isFieldHidden('updated_at')) {
            $columns[] =
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::modelHasColumn('deleted_at') && ! static::isFieldHidden('deleted_at')) {
            $columns[] =
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true);
        }

        return $columns;
    }

    protected static function additionalTableColumns(): array
    {
        return []; // hook for additional columns
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
                    $newRecord = static::duplicateRecord($record);
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
                ->form(function () {
                    $fields = [];

                    if (static::modelHasColumn('status')) {
                        $fields[] =
                            Select::make('status')
                                ->enum(ContentStatus::class)
                                ->options(ContentStatus::class)
                                ->nullable();
                    }

                    if (static::modelHasColumn('author_id')) {
                        $fields[] =
                            Select::make('author_id')
                                ->relationship('author', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable();
                    }

                    if (static::modelHasColumn('published_at')) {
                        $fields[] =
                            DateTimePicker::make('published_at')
                                ->nullable();
                    }

                    return $fields;
                })
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

    // Helper methods

    /**
     * Check if a field should be hidden
     */
    protected static function isFieldHidden(string $field): bool
    {
        return in_array($field, static::hiddenFields());
    }

    // Check if the model has a specific column
    protected static function modelHasColumn(string $column): bool
    {
        $modelClass = app(static::$model);

        return in_array($column, $modelClass->getFillable()) ||
            array_key_exists($column, $modelClass->getCasts()) ||
            $modelClass->hasAttribute($column);
    }

    // Check if the model has a specific relationship
    protected static function modelHasRelationship(string $relationship): bool
    {
        $modelClass = app(static::$model);

        // Check if the method exists on the model
        if (! method_exists($modelClass, $relationship)) {
            return false;
        }

        try {
            // Call the relationship method and check if it returns a Relation instance
            $result = $modelClass->{$relationship}();

            return $result instanceof \Illuminate\Database\Eloquent\Relations\Relation;
        } catch (\Exception $e) {
            // If calling the method throws an exception, it's likely not a relationship
            return false;
        }
    }

    /**
     * Replicate actions for table records
     */
    protected static function duplicateRecord(\Illuminate\Database\Eloquent\Model $record): \Illuminate\Database\Eloquent\Model
    {
        $newRecord = $record->replicate();

        static::handleMultilingualSlugs($record, $newRecord);
        static::resetDraftStatus($newRecord);

        $newRecord->save();

        static::replicateRelationships($record, $newRecord);

        return $newRecord;
    }

    protected static function handleMultilingualSlugs(\Illuminate\Database\Eloquent\Model $record, \Illuminate\Database\Eloquent\Model $newRecord): void
    {
        $originalSlugs = $newRecord->getTranslations('slug');
        $newSlugs = [];
        $locales = static::getAvailableLocales();

        foreach ($locales as $locale) {
            $originalSlug = Arr::get($originalSlugs, $locale);
            $newSlugs[$locale] = $originalSlug ? static::generateUniqueSlug($originalSlug, $locale) : null;
        }

        $newRecord->setTranslations('slug', $newSlugs);
    }

    protected static function generateUniqueSlug(string $originalSlug, string $locale): string
    {
        $count = 1;
        $newSlug = $originalSlug;
        $modelClass = static::getModel();

        while ($modelClass::whereJsonContains("slug->{$locale}", $newSlug)->exists()) {
            $newSlug = "{$originalSlug}-copy-{$count}";
            $count++;
        }

        return $newSlug;
    }

    protected static function resetDraftStatus(\Illuminate\Database\Eloquent\Model $newRecord): void
    {
        // Set status to Draft if the field exists
        if (static::hasAttribute($newRecord, 'status')) {
            $newRecord->status = ContentStatus::Draft;
        }

        // Clear published_at if the field exists
        if (static::hasAttribute($newRecord, 'published_at')) {
            $newRecord->published_at = null;
        }
    }

    protected static function hasAttribute(\Illuminate\Database\Eloquent\Model $model, string $attribute): bool
    {
        return array_key_exists($attribute, $model->getAttributes()) || $model->isFillable($attribute);
    }

    protected static function getAvailableLocales(): array
    {
        return array_keys(config('cms.language_available'));
    }

    /**
     * Replicate many-to-many and other relationships
     */
    protected static function getRelationshipsToReplicate(): array
    {
        return ['categories', 'tags']; // Default relationships
    }

    protected static function replicateRelationships(\Illuminate\Database\Eloquent\Model $original, \Illuminate\Database\Eloquent\Model $replica): void
    {

        $relationshipsToReplicate = static::getRelationshipsToReplicate();

        foreach ($relationshipsToReplicate as $relationshipName) {
            if (static::modelHasRelationship($relationshipName)) {
                try {
                    $relationship = $original->{$relationshipName}();

                    // Handle BelongsToMany relationships
                    if ($relationship instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                        $relatedIds = $original->{$relationshipName}()->pluck($relationship->getRelatedKeyName())->toArray();
                        if (! empty($relatedIds)) {
                            $replica->{$relationshipName}()->attach($relatedIds);
                        }
                    }

                } catch (\Exception $e) {
                    // Log the error or handle it gracefully
                    \Log::warning("Failed to replicate relationship '{$relationshipName}': ".$e->getMessage());
                }
            }
        }
    }
}
