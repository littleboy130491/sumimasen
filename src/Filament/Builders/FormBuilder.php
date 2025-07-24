<?php

namespace Littleboy130491\Sumimasen\Filament\Builders;

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
use Filament\Forms\Get;
use Filament\Forms\Set;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Filament\Forms\Components\SeoFields;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class FormBuilder
{
    public function __construct(
        protected string $modelClass,
        protected array $hiddenFields = [],
        protected ?object $resourceClass = null
    ) {}

    public function buildSchema(): array
    {
        return [
            ...$this->getTopSection(),
            ...$this->getBottomSection(),
        ];
    }

    protected function getTopSection(): array
    {
        return [
            Grid::make()
                ->columns([
                    'sm' => 3,
                    'xl' => 4,
                    '2xl' => 4,
                ])
                ->schema([
                    Section::make('Main Fields')
                        ->schema([
                            Translate::make()
                                ->columns(2)
                                ->schema(function (string $locale): array {
                                    return $this->topLeftSchema($locale);
                                })
                                ->contained(false)
                                ->actions([
                                    $this->resourceClass?->copyFromDefaultLangAction() ?? [],
                                ]),
                        ])
                        ->columnSpan([
                            'sm' => 2,
                            'xl' => 3,
                            '2xl' => 3,
                        ])
                        ->collapsible(),
                    Section::make('Settings')
                        ->schema($this->topRightSchema())
                        ->columnSpan([
                            'sm' => 1,
                            'xl' => 1,
                            '2xl' => 1,
                        ])
                        ->collapsible(),
                ]),
        ];
    }

    protected function topLeftSchema(string $locale): array
    {
        return [
            ...$this->buildTitleSlugFields($locale),
            ...$this->buildContentFields($locale),
            ...$this->buildAdditionalTranslatableFields($locale),
        ];
    }

    protected function topRightSchema(): array
    {
        return [
            ...$this->buildFeaturedImageField(),
            ...$this->buildRelationshipsFields(),
            ...$this->buildAuthorRelationshipField(),
            ...$this->buildStatusField(),
            ...$this->buildTemplateField(),
            ...$this->buildFeaturedField(),
            ...$this->buildPublishedDateField(),
            ...$this->buildMenuOrderField(),
        ];
    }

    protected function getBottomSection(): array
    {
        $sections = [];

        if (! empty($this->buildAdditionalNonTranslatableFields())) {
            $sections[] = Section::make('Additional Fields')
                ->schema($this->buildAdditionalNonTranslatableFields())
                ->columns(2);
        }

        if ($this->modelHasColumn('custom_fields') && ! $this->isFieldHidden('custom_fields')) {
            $sections[] = Section::make('Custom Fields')
                ->schema([
                    KeyValue::make('custom_fields')->nullable(),
                ])
                ->columns(1)
                ->collapsible();
        }

        $sections[] = Section::make('SEO Settings')
            ->schema([
                SeoFields::make(),
            ])
            ->collapsible();

        return $sections;
    }

    protected function buildTitleSlugFields(string $locale, string $tableName = ''): array
    {
        $defaultLocale = config('cms.default_language', 'en');

        if ($tableName === '') {
            $tableName = app($this->modelClass)->getTable();
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
                        UTR::for($tableName, 'slug')->ignore($get('id')),
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

    protected function buildContentFields(?string $locale): array
    {
        $fields = [];

        if ($this->modelHasColumn('content') && ! $this->isFieldHidden('content')) {
            $fields[] = TiptapEditor::make('content')
                ->profile('simple')
                ->nullable()
                ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                ->columnSpanFull();
        }

        if ($this->modelHasColumn('excerpt') && ! $this->isFieldHidden('excerpt')) {
            $fields[] = Textarea::make('excerpt')
                ->nullable()
                ->columnSpanFull();
        }

        if ($this->modelHasColumn('section') && ! $this->isFieldHidden('section')) {
            $fields[] = FormsBuilder::make('section')
                ->collapsed(false)
                ->blocks($this->resourceClass?->getContentBlocks() ?? [])
                ->cloneable()
                ->columnSpanFull();
        }

        return $fields;
    }

    protected function buildAdditionalTranslatableFields(?string $locale): array
    {
        return [];
    }

    protected function buildAdditionalNonTranslatableFields(): array
    {
        return [];
    }

    protected function buildFeaturedImageField(): array
    {
        if (! $this->modelHasColumn('featured_image') || $this->isFieldHidden('featured_image')) {
            return [];
        }

        return [
            CuratorPicker::make('featured_image')
                ->relationship('featuredImage', 'id')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']),
        ];
    }

    protected function buildRelationshipsFields(): array
    {
        return [];
    }

    protected function buildAuthorRelationshipField(): array
    {
        if (! $this->modelHasColumn('author_id') || $this->isFieldHidden('author_id')) {
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

    protected function buildStatusField(): array
    {
        if (! $this->modelHasColumn('status') || $this->isFieldHidden('status')) {
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

    protected function buildTemplateField(string $subPath = ''): array
    {
        if (! $this->modelHasColumn('template') || $this->isFieldHidden('template')) {
            return [];
        }

        return $this->getTemplateOptions($subPath);
    }

    protected function getTemplateOptions(string $subPath = ''): array
    {
        return [
            Select::make('template')
                ->options(function () use ($subPath) {
                    return $this->fetchRawTemplateData($subPath);
                })
                ->nullable()
                ->placeholder('Default System Template')
                ->default(null)
                ->label('Template')
                ->dehydrateStateUsing(function ($state) use ($subPath) {
                    if ($state === null) {
                        return null;
                    }

                    $currentFileOptions = $this->fetchRawTemplateData($subPath);
                    $validFileOptionKeys = array_keys($currentFileOptions);

                    if (in_array($state, $validFileOptionKeys, true)) {
                        return $state;
                    } else {
                        return null;
                    }
                }),
        ];
    }

    protected function fetchRawTemplateData(string $subPath = ''): array
    {
        $options = [];
        $fullPath = 'views/templates/'.($subPath ? ltrim($subPath, '/') : '');
        $templatesPath = resource_path(rtrim($fullPath, '/'));

        if (File::isDirectory($templatesPath)) {
            $files = File::files($templatesPath);
            foreach ($files as $file) {
                $filename = $file->getFilenameWithoutExtension();
                if (! empty($filename)) {
                    $options[$filename] = $filename;
                }
            }
        }

        return $options;
    }

    protected function buildFeaturedField(): array
    {
        if (! $this->modelHasColumn('featured') || $this->isFieldHidden('featured')) {
            return [];
        }

        return [
            Toggle::make('featured')->default(false),
        ];
    }

    protected function buildPublishedDateField(): array
    {
        if (! $this->modelHasColumn('published_at') || $this->isFieldHidden('published_at')) {
            return [];
        }

        return [
            DateTimePicker::make('published_at')->nullable(),
        ];
    }

    protected function buildMenuOrderField(): array
    {
        if (! $this->modelHasColumn('menu_order') || $this->isFieldHidden('menu_order')) {
            return [];
        }

        return [
            TextInput::make('menu_order')
                ->numeric()
                ->default(0),
        ];
    }

    public function buildTaxonomyRelationshipField(string $relationship, ?string $tableName = '', bool $multiple = true): array
    {
        if (! $this->modelHasRelationship($relationship) || $this->isFieldHidden($relationship)) {
            return [];
        }

        if (empty($tableName)) {
            if (Schema::hasTable($relationship)) {
                $tableName = $relationship;
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

        $permissionName = 'create_'.Str::snake(Str::singular($relationship), '::');

        if (auth()->user()->can($permissionName)) {
            $select->createOptionForm([
                Translate::make()
                    ->columnSpanFull()
                    ->schema(function (string $locale) use ($tableName): array {
                        return [
                            ...$this->buildTitleSlugFields($locale, $tableName),
                        ];
                    }),
            ]);
        }

        return [$select];
    }

    public function buildParentRelationshipField(): array
    {
        if (! $this->modelHasColumn('parent_id') || $this->isFieldHidden('parent_id')) {
            return [];
        }

        return [
            Select::make('parent_id')
                ->relationship('parent', 'title', ignoreRecord: true),
        ];
    }

    protected function isFieldHidden(string $field): bool
    {
        return in_array($field, $this->hiddenFields);
    }

    protected function modelHasColumn(string $column): bool
    {
        $modelClass = app($this->modelClass);

        return in_array($column, $modelClass->getFillable()) ||
            array_key_exists($column, $modelClass->getCasts()) ||
            $modelClass->hasAttribute($column);
    }

    protected function modelHasRelationship(string $relationship): bool
    {
        $modelClass = app($this->modelClass);

        if (! method_exists($modelClass, $relationship)) {
            return false;
        }

        try {
            $result = $modelClass->{$relationship}();

            return $result instanceof \Illuminate\Database\Eloquent\Relations\Relation;
        } catch (\Exception $e) {
            return false;
        }
    }
}
