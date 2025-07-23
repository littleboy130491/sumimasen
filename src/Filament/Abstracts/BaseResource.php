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
use Filament\Notifications\Notification;

abstract class BaseResource extends Resource
{
    use HasContentBlocks;
    use HasCopyFromDefaultLangButton;

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
                                ->contained(false),
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
        if (!empty(static::additionalNonTranslatableFormFields())) {
            $sections[] = Section::make('Additional Fields')
                ->schema(static::additionalNonTranslatableFormFields())
                ->columns(2);
        }

        // Custom Fields Section
        if (static::modelHasColumn('custom_fields') && !static::isFieldHidden('custom_fields')) {
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

                    if ($operation === 'edit' && !empty($get('slug.' . $locale))) {
                        return;
                    }

                    $set('slug.' . $locale, $state ? Str::slug($state) : null);
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
                            $titleValue = $get('title.' . $locale);
                            if ($titleValue) {
                                $slug = Str::slug($titleValue);
                                $set('slug.' . $locale, $slug);
                            }
                        })
                ),
        ];
    }

    protected static function formContentFields(?string $locale): array
    {
        $fields = [];

        if (static::modelHasColumn('content') && !static::isFieldHidden('content')) {
            $fields[] = TiptapEditor::make('content')
                ->profile('simple')
                ->nullable()
                ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                ->columnSpanFull();
        }

        if (static::modelHasColumn('excerpt') && !static::isFieldHidden('excerpt')) {
            $fields[] = Textarea::make('excerpt')
                ->nullable()
                ->columnSpanFull();
        }

        if (static::modelHasColumn('section') && !static::isFieldHidden('section')) {
            $fields[] = FormsBuilder::make('section')
                ->collapsed(false)
                ->blocks(static::getContentBlocks())
                ->cloneable()
                ->columnSpanFull()
                ->hintActions([
                    static::copyFromDefaultLangAction()->with(['locale' => $locale]),
                ]);
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
        if (!static::modelHasColumn('featured_image') || static::isFieldHidden('featured_image')) {
            return [];
        }

        return [
            CuratorPicker::make('featured_image')
                ->relationship('featuredImage', 'id')
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
        if (!static::modelHasRelationship($relationship) || static::isFieldHidden($relationship)) {
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
        $permissionName = 'create_' . Str::snake(Str::singular($relationship), '::');

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
        if (!static::modelHasColumn('parent_id') || static::isFieldHidden('parent_id')) {
            return [];
        }

        return [
            Select::make('parent_id')
                ->relationship('parent', 'title', ignoreRecord: true),
        ];
    }

    protected static function formAuthorRelationshipField(): array
    {
        if (!static::modelHasColumn('author_id') || static::isFieldHidden('author_id')) {
            return [];
        }

        return [
            Select::make('author_id')
                ->relationship('author', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->default(fn() => auth()->id()),
        ];
    }

    protected static function formStatusField(): array
    {
        if (!static::modelHasColumn('status') || static::isFieldHidden('status')) {
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
        if (!static::modelHasColumn('template') || static::isFieldHidden('template')) {
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
        $fullPath = 'views/templates/' . ($subPath ? ltrim($subPath, '/') : '');
        $templatesPath = resource_path(rtrim($fullPath, '/'));

        if (File::isDirectory($templatesPath)) {
            $files = File::files($templatesPath);
            foreach ($files as $file) {
                $filename = $file->getFilenameWithoutExtension();
                // Ensure filename is not empty and use it as both key and value
                if (!empty($filename)) {
                    $options[$filename] = $filename;
                }
            }
        }

        return $options;
    }

    protected static function formFeaturedField(): array
    {
        if (!static::modelHasColumn('featured') || static::isFieldHidden('featured')) {
            return [];
        }

        return [
            Toggle::make('featured')
                ->default(false),
        ];
    }

    protected static function formPublishedDateField(): array
    {
        if (!static::modelHasColumn('published_at') || static::isFieldHidden('published_at')) {
            return [];
        }

        return [
            DateTimePicker::make('published_at')
                ->nullable(),
        ];
    }

    protected static function formMenuOrderField(): array
    {
        if (!static::modelHasColumn('menu_order') || static::isFieldHidden('menu_order')) {
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
                ...static::tableHeaderActions(),
            );
    }

    protected static function tableColumns(): array
    {
        $columns = [
            TextColumn::make('title')
                ->searchable()
                ->sortable(),

            TextColumn::make('author.name')
                ->searchable()
                ->sortable()
                ->toggleable(),

            ...static::tableDateColumns(),

            ToggleColumn::make('status')
                ->updateStateUsing(function ($record, $state) {
                    $record->status = $state ? ContentStatus::Published : ContentStatus::Draft;
                    $record->save();
                })
                ->getStateUsing(function ($record) {
                    return $record->status === ContentStatus::Published;
                })
                ->sortable(),

            ...static::additionalTableColumns(), // Hook for additional columns
        ];

        if (static::modelHasColumn('featured')) {
            $columns[] = ToggleColumn::make('featured')
                ->sortable()
                ->toggleable();
        }

        return $columns;
    }

    protected static function tableDateColumns(): array
    {
        $dateColumns = [];

        if (static::modelHasColumn('published_at')) {
            $dateColumns[] = TextColumn::make('published_at')
                ->label('Published Date')
                ->date()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }
        if (static::modelHasColumn('created_at')) {
            $dateColumns[] = TextColumn::make('created_at')
                ->label('Created Date')
                ->date()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }
        if (static::modelHasColumn('updated_at')) {
            $dateColumns[] = TextColumn::make('updated_at')
                ->label('Updated Date')
                ->date()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        return $dateColumns;
    }

    protected static function additionalTableColumns(): array
    {
        return []; // Hook for additional table columns
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
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->action(function ($record) {
                    $newRecord = static::duplicateRecord($record);
                    Notification::make()
                        ->success()
                        ->title('Duplicated successfully')
                        ->body('The record has been duplicated. You are now editing the new draft.')
                        ->send();

                    return redirect(static::getResource()::getUrl('edit', ['record' => $newRecord->id]));

                })
                ->requiresConfirmation()
                ->modalHeading('Duplicate record')
                ->modalDescription('Are you sure you want to duplicate this record? A new draft will be created.')
                ->modalSubmitActionLabel('Yes, duplicate it'),
        ];
    }

    protected static function tableBulkActions(): array
    {
        return [
            static::tableEditBulkAction(),
            Tables\Actions\DeleteBulkAction::make(),
            static::tableExportBulkAction(),
        ];
    }

    protected static function tableEditBulkAction(): array
    {
        $bulkActions = [];

        $fields = [];
        if (static::modelHasColumn('status')) {
            $fields[] = Select::make('status')
                ->enum(ContentStatus::class)
                ->options(ContentStatus::class);
        }

        if (static::modelHasColumn('author_id')) {
            $fields[] = Select::make('author_id')
                ->relationship('author', 'name')
                ->label('Author');
        }

        if (static::modelHasColumn('published_at')) {
            $fields[] = DateTimePicker::make('published_at')
                ->label('Published Date');
        }

        if (static::modelHasRelationship('categories')) {
            $fields[] = Select::make('categories')
                ->relationship('categories', 'title')
                ->multiple()
                ->label('Categories');
        }

        if (static::modelHasRelationship('tags')) {
            $fields[] = Select::make('tags')
                ->relationship('tags', 'title')
                ->multiple()
                ->label('Tags');
        }

        if (!empty($fields)) {
            $bulkActions[] = Tables\Actions\BulkActionGroup::make([
                Tables\Actions\EditAction::make('edit')
                    ->form($fields),
            ])->label('Edit');
        }

        return $bulkActions;
    }

    protected static function tableExportBulkAction(): array
    {
        return [];
    }

    protected static function tableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make(),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * UTILITY FUNCTIONS
     *
     * These functions are used by the form and table builders.
     */

    // Check if a field should be hidden
    protected static function isFieldHidden(string $field): bool
    {
        return in_array($field, static::hiddenFields(), true);
    }

    // Check if the model has a specific column
    protected static function modelHasColumn(string $column): bool
    {
        $modelClass = static::$model;
        $modelInstance = new $modelClass;

        return Schema::hasColumn($modelInstance->getTable(), $column);
    }

    // Check if the model has a specific relationship
    protected static function modelHasRelationship(string $relationship): bool
    {
        $modelClass = static::$model;
        $modelInstance = new $modelClass;

        if (method_exists($modelInstance, $relationship)) {
            $relation = $modelInstance->$relationship();
            return $relation instanceof \Illuminate\Database\Eloquent\Relations\Relation;
        }

        return false;
    }

    /**
     * DUPLICATION LOGIC
     */

    // Main duplication function
    protected static function duplicateRecord(\Illuminate\Database\Eloquent\Model $record): \Illuminate\Database\Eloquent\Model
    {
        $newRecord = $record->replicate();
        static::resetDraftStatus($newRecord);
        $newRecord->save();

        static::handleMultilingualSlugs($record, $newRecord);
        static::replicateRelationships($record, $newRecord);

        return $newRecord;
    }

    protected static function handleMultilingualSlugs(\Illuminate\Database\Eloquent\Model $record, \Illuminate\Database\Eloquent\Model $newRecord): void
    {
        if (static::isTranslatable() && static::modelHasColumn('slug')) {
            $locales = static::getAvailableLocales();
            foreach ($locales as $locale) {
                $originalSlug = $record->getTranslation('slug', $locale, false);
                if ($originalSlug) {
                    $newSlug = static::generateUniqueSlug($originalSlug, $locale);
                    $newRecord->setTranslation('slug', $locale, $newSlug);
                }
            }
            $newRecord->save();
        }
    }

    protected static function generateUniqueSlug(string $originalSlug, string $locale): string
    {
        $modelClass = static::$model;
        $count = 1;
        $newSlug = $originalSlug . '-' . $count;

        while ($modelClass::where("slug->{$locale}", $newSlug)->exists()) {
            $count++;
            $newSlug = $originalSlug . '-' . $count;
        }

        return $newSlug;
    }

    protected static function resetDraftStatus(\Illuminate\Database\Eloquent\Model $newRecord): void
    {
        if (static::hasAttribute($newRecord, 'status')) {
            $newRecord->status = ContentStatus::Draft;
        }
        if (static::hasAttribute($newRecord, 'published_at')) {
            $newRecord->published_at = null;
        }
    }

    protected static function hasAttribute(\Illuminate\Database\Eloquent\Model $model, string $attribute): bool
    {
        return in_array($attribute, $model->getFillable()) || array_key_exists($attribute, $model->getCasts());
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
                        if (!empty($relatedIds)) {
                            $replica->{$relationshipName}()->attach($relatedIds);
                        }
                    }

                } catch (\Exception $e) {
                    // Log the error or handle it gracefully
                    \Log::warning("Failed to replicate relationship '{$relationshipName}': " . $e->getMessage());
                }
            }
        }
    }
}
