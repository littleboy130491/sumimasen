<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class CreateModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:model:from-yaml
                            {yaml_file? : The path to the YAML definition file (defaults to schemas/models.yaml)}
                            {--model= : Specify a single model from the YAML to generate}
                            {--force : Overwrite existing model files without asking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Eloquent model file(s) including Enum constants from a YAML definition file';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();
        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $yamlFilePath = $this->argument('yaml_file') ?? base_path('schemas/models.yaml');
        $specificModel = $this->option('model');
        $force = $this->option('force');

        if (! $this->files->exists($yamlFilePath)) {
            $this->error("YAML file not found at: {$yamlFilePath}");
            return 1;
        }

        try {
            $yamlContent = $this->files->get($yamlFilePath);
            $schema = Yaml::parse($yamlContent);
        } catch (ParseException $exception) {
            $this->error("Error parsing YAML file: {$exception->getMessage()}");
            return 1;
        }

        if (! isset($schema['models']) || ! is_array($schema['models'])) {
            $this->error("Invalid YAML structure. Missing 'models' key or it's not an array.");
            return 1;
        }

        $modelsToProcess = $schema['models'];

        // Filter for a specific model if the option is provided
        if ($specificModel) {
            if (! isset($modelsToProcess[$specificModel])) {
                $this->error("Model '{$specificModel}' not found in the YAML file.");
                return 1;
            }
            $modelsToProcess = [$specificModel => $modelsToProcess[$specificModel]];
        }

        $generatedCount = 0;
        foreach ($modelsToProcess as $modelName => $modelDefinition) {
            $this->info("Processing model: {$modelName}");
            if ($this->generateModelFile($modelName, $modelDefinition, $force)) {
                $generatedCount++;
            }
        }

        if ($generatedCount > 0) {
            $this->composer->dumpAutoloads();
            $this->info('Composer autoload files regenerated.');
        } else {
            $this->info('No new model files were generated.');
        }

        $this->info('Model generation process completed.');
        return 0;
    }

    /**
     * Generate the Eloquent model file.
     *
     * @return bool Returns true if the file was generated, false otherwise.
     */
    protected function generateModelFile(string $modelName, array $definition, bool $force): bool
    {
        $namespace = 'App\\Models';
        $className = Str::studly($modelName);
        $filePath = app_path("Models/{$className}.php");

        // Check if file exists and prompt for overwrite unless --force is used
        if ($this->files->exists($filePath) && ! $force) {
            if (! $this->confirm("Model file [{$filePath}] already exists. Overwrite?", false)) {
                $this->line("Skipping generation for model: {$className}");
                return false;
            }
        }

        // Ensure the directory exists
        $directoryPath = dirname($filePath);
        if (! $this->files->isDirectory($directoryPath)) {
            $this->files->makeDirectory($directoryPath, 0755, true);
        }

        // Build the model content, passing the namespace
        $content = $this->buildModelContent($namespace, $className, $definition);

        // Write the file
        if ($this->files->put($filePath, $content) !== false) {
            $this->line("<info>Created Model:</info> {$filePath}");
            return true;
        } else {
            $this->error("Failed to write model file: {$filePath}");
            return false;
        }
    }

    /**
     * Build the full content of the model file.
     */
    protected function buildModelContent(string $namespace, string $className, array $definition): string
    {
        $uses = $this->buildUses($namespace, $className, $definition);
        $traits = $this->buildTraits($definition);
        $constants = $this->buildConstants($definition);
        $fillable = $this->buildFillable($definition);
        $casts = $this->buildCasts($definition);
        $translatable = $this->buildTranslatable($definition);
        $appends = $this->buildAppends($definition);
        $accessors = $this->buildAccessors($definition);
        $customMethods = $this->buildCustomMethods($definition);
        $relationships = $this->buildRelationships($namespace, $definition);

        return <<<PHP
<?php

namespace {$namespace};

{$uses}

class {$className} extends Model
{
{$traits}
{$constants}
{$fillable}
{$casts}
{$translatable}
{$appends}
{$accessors}
    //--------------------------------------------------------------------------
    // Relationships
    //--------------------------------------------------------------------------
{$relationships}
{$customMethods}
}

PHP;
    }

    /**
     * Build the 'use' statements based on required traits and relationships.
     */
    protected function buildUses(string $namespace, string $className, array $definition): string
    {
        $uses = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Illuminate\Database\Eloquent\Model',
        ];

        $relationshipTypes = [];
        $relatedModelClasses = [];

        // Add trait imports
        if (! empty($definition['traits'])) {
            foreach ($definition['traits'] as $trait) {
                $uses[] = $trait;
            }
        }

        // Add specific imports based on fields
        if (! empty($definition['fields'])) {
            foreach ($definition['fields'] as $fieldName => $fieldDef) {
                // Add ContentStatus enum import for status fields
                if ($fieldName === 'status') {
                    $uses[] = 'Littleboy130491\Sumimasen\Enums\ContentStatus';
                }

                // Add Curator Media import for featured_image
                if ($fieldName === 'featured_image') {
                    $uses[] = 'Awcodes\Curator\Models\Media';
                    $relationshipTypes[] = 'Illuminate\Database\Eloquent\Relations\BelongsTo';
                }

                // Add custom enum class imports
                if (isset($fieldDef['enum_class']) && !empty($fieldDef['enum_class'])) {
                    $uses[] = ltrim($fieldDef['enum_class'], '\\');
                }
            }
        }

        // Add relationship imports
        if (! empty($definition['relationships'])) {
            foreach ($definition['relationships'] as $relDef) {
                $type = $relDef['type'] ?? null;
                $relatedModel = $relDef['model'] ?? null;

                // Add relationship type namespace
                if ($type) {
                    switch (strtolower($type)) {
                        case 'belongsto':
                            $relationshipTypes[] = 'Illuminate\Database\Eloquent\Relations\BelongsTo';
                            break;
                        case 'belongstomany':
                            $relationshipTypes[] = 'Illuminate\Database\Eloquent\Relations\BelongsToMany';
                            break;
                        case 'hasmany':
                            $relationshipTypes[] = 'Illuminate\Database\Eloquent\Relations\HasMany';
                            break;
                        case 'hasone':
                            $relationshipTypes[] = 'Illuminate\Database\Eloquent\Relations\HasOne';
                            break;
                        case 'morphto':
                            $relationshipTypes[] = 'Illuminate\Database\Eloquent\Relations\MorphTo';
                            break;
                        case 'morphmany':
                            $relationshipTypes[] = 'Illuminate\Database\Eloquent\Relations\MorphMany';
                            break;
                    }
                }

                // Add related model namespace
                if ($relatedModel) {
                    $relatedModelFqn = $namespace.'\\'.Str::studly($relatedModel);
                    $relatedClassName = Str::afterLast($relatedModelFqn, '\\');

                    if ($relatedClassName !== $className) {
                        $relatedModelClasses[] = $relatedModelFqn;
                    }
                }
            }
        }

        // Merge all uses and keep unique
        $uses = array_unique(array_merge($uses, $relationshipTypes, $relatedModelClasses));

        // Remove current model from uses
        $currentModelFqn = $namespace.'\\'.$className;
        $uses = array_filter($uses, fn ($use) => $use !== $currentModelFqn);

        sort($uses);

        return collect($uses)->map(fn ($use) => "use {$use};")->implode("\n");
    }

    /**
     * Build the 'use TraitName;' lines inside the class.
     */
    protected function buildTraits(array $definition): string
    {
        $traits = ['HasFactory'];

        if (! empty($definition['traits'])) {
            foreach ($definition['traits'] as $trait) {
                $traits[] = class_basename($trait);
            }
        }

        $traits = array_unique($traits);

        if (empty($traits)) {
            return '';
        }

        return '    use '.implode(', ', $traits).";\n";
    }

    /**
     * Build constant definitions for enum fields.
     */
    protected function buildConstants(array $definition): string
    {
        $constants = [];
        if (empty($definition['fields'])) {
            return '';
        }

        foreach ($definition['fields'] as $fieldName => $fieldDef) {
            if (
                strtolower($fieldDef['type'] ?? '') === 'enum' &&
                ! empty($fieldDef['enum']) &&
                is_array($fieldDef['enum']) &&
                empty($fieldDef['enum_class'])
            ) {
                $constantName = Str::upper(Str::snake($fieldName)).'_OPTIONS';

                $optionsArray = collect($fieldDef['enum'])
                    ->mapWithKeys(function ($value) {
                        return [$value => Str::title(str_replace('_', ' ', $value))];
                    })
                    ->map(fn ($label, $value) => "'{$value}' => '{$label}'")
                    ->implode(', ');

                $constants[] = "    public const {$constantName} = [{$optionsArray}];";
            }
        }

        if (empty($constants)) {
            return '';
        }

        return implode("\n", $constants)."\n";
    }

    /**
     * Build the $fillable property definition.
     */
    protected function buildFillable(array $definition): string
    {
        $fillable = [];

        // Add fields from the 'fields' section
        if (! empty($definition['fields'])) {
            $fillable = array_keys($definition['fields']);
        }

        // Add inferred foreign keys from 'belongsTo' relationships
        if (! empty($definition['relationships'])) {
            foreach ($definition['relationships'] as $relationName => $relDef) {
                if (strtolower($relDef['type'] ?? '') === 'belongsto') {
                    $foreignKey = $relDef['foreign_key'] ?? Str::snake($relationName).'_id';
                    if (! isset($definition['fields'][$foreignKey])) {
                        $fillable[] = $foreignKey;
                    }
                }
            }
        }

        $fillable = array_unique($fillable);
        sort($fillable);

        if (empty($fillable)) {
            return "    // protected \$guarded = []; // Or define fillable fields\n";
        }

        $fillableString = collect($fillable)->map(fn ($field) => "'{$field}'")->implode(",\n        ");

        return <<<PHP

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected \$fillable = [
        {$fillableString},
    ];

PHP;
    }

    /**
     * Build the $casts property definition.
     */
    protected function buildCasts(array $definition): string
    {
        $casts = [];
        
        if (empty($definition['fields'])) {
            return '';
        }

        foreach ($definition['fields'] as $fieldName => $fieldDef) {
            $type = strtolower($fieldDef['type'] ?? 'string');
            $isTranslatable = $fieldDef['translatable'] ?? false;

            switch ($type) {
                case 'bool':
                case 'boolean':
                    $casts[$fieldName] = 'boolean';
                    break;
                    
                case 'int':
                case 'integer':
                case 'tinyint':
                case 'bigint':
                    if ($fieldName !== 'id') {
                        $casts[$fieldName] = 'integer';
                    }
                    break;
                    
                case 'float':
                case 'double':
                case 'decimal':
                    $precision = $fieldDef['scale'] ?? 2;
                    $casts[$fieldName] = "decimal:{$precision}";
                    break;
                    
                case 'date':
                    $casts[$fieldName] = 'date';
                    break;
                    
                case 'datetime':
                case 'timestamp':
                case 'datetimetz':
                case 'timestamptz':
                    $casts[$fieldName] = 'datetime';
                    break;
                    
                case 'json':
                    if (! $isTranslatable || $fieldName === 'section') {
                        $casts[$fieldName] = 'array';
                    }
                    break;
                    
                case 'enum':
                    // Special handling for status field with ContentStatus enum
                    if ($fieldName === 'status') {
                        $casts[$fieldName] = 'ContentStatus::class';
                    } elseif (! empty($fieldDef['enum_class'])) {
                        $enumClass = class_basename($fieldDef['enum_class']);
                        $casts[$fieldName] = "{$enumClass}::class";
                    } else {
                        $casts[$fieldName] = 'string';
                    }
                    break;
            }
        }

        // Add common casts
        $commonCasts = [
            'published_at' => 'datetime',
            'custom_fields' => 'array',
            'featured' => 'boolean',
            'menu_order' => 'integer',
        ];

        foreach ($commonCasts as $field => $cast) {
            if (isset($definition['fields'][$field]) && !isset($casts[$field])) {
                if ($field === 'status') {
                    $casts[$field] = 'ContentStatus::class';
                } else {
                    $casts[$field] = $cast;
                }
            }
        }

        if (empty($casts)) {
            return '';
        }

        $castsString = collect($casts)
            ->map(function ($castType, $field) {
                if (str_contains($castType, '::class')) {
                    return "'{$field}' => {$castType}";
                }
                return "'{$field}' => '{$castType}'";
            })
            ->implode(",\n        ");

        return <<<PHP

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected \$casts = [
        {$castsString},
    ];

PHP;
    }

    /**
     * Build the $translatable property definition.
     */
    protected function buildTranslatable(array $definition): string
    {
        $translatable = [];
        
        if (! empty($definition['fields'])) {
            foreach ($definition['fields'] as $fieldName => $fieldDef) {
                if ($fieldDef['translatable'] ?? false) {
                    $translatable[] = $fieldName;
                }
            }
        }

        if (empty($translatable)) {
            return '';
        }

        sort($translatable);
        $translatableString = collect($translatable)->map(fn ($field) => "'{$field}'")->implode(",\n        ");

        return <<<PHP

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public \$translatable = [
        {$translatableString},
    ];

PHP;
    }

    /**
     * Build the relationship method definitions.
     */
    protected function buildRelationships(string $namespace, array $definition): string
    {
        $methods = [];

        // Add featuredImage relationship if 'featured_image' field exists
        if (! empty($definition['fields']) && isset($definition['fields']['featured_image'])) {
            $methods[] = <<<'PHP'

    /**
     * Define the featuredImage relationship to Curator Media.
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image', 'id');
    }
PHP;
        }

        // Build other relationships from the 'relationships' section
        if (! empty($definition['relationships'])) {
            foreach ($definition['relationships'] as $methodName => $relDef) {
                $type = $relDef['type'] ?? null;
                $relatedModel = $relDef['model'] ?? null;
                $foreignKey = $relDef['foreign_key'] ?? null;

                if (! $type) {
                    continue;
                }

                // Handle morphTo relationships
                if (strtolower($type) === 'morphto') {
                    $relationshipType = 'MorphTo';
                    $relationshipMethod = 'morphTo';

                    $methods[] = <<<PHP

    /**
     * Define the {$methodName} relationship.
     */
    public function {$methodName}(): {$relationshipType}
    {
        return \$this->{$relationshipMethod}();
    }
PHP;
                    continue;
                }

                if (! $relatedModel) {
                    continue;
                }

                $relatedModelClass = Str::studly($relatedModel);
                $relationshipType = Str::studly($type);
                $relationshipMethod = Str::camel($type);

                // Construct arguments string
                $arguments = ["{$relatedModelClass}::class"];

                // Handle special cases for different relationship types
                if (strtolower($type) === 'morphmany') {
                    $morphName = $relDef['name'] ?? Str::snake($methodName);
                    $arguments = ["{$relatedModelClass}::class", "'{$morphName}'"];
                } elseif ($foreignKey) {
                    $arguments[] = "'{$foreignKey}'";
                }

                $argumentsString = implode(', ', $arguments);

                $methods[] = <<<PHP

    /**
     * Define the {$methodName} relationship.
     */
    public function {$methodName}(): {$relationshipType}
    {
        return \$this->{$relationshipMethod}({$argumentsString});
    }
PHP;
            }
        }

        return implode("\n", $methods);
    }

    /**
     * Build the $appends property definition.
     */
    protected function buildAppends(array $definition): string
    {
        if (empty($definition['special_methods']['appends'])) {
            return '';
        }

        $appends = $definition['special_methods']['appends'];
        $appendsString = collect($appends)->map(fn ($field) => "'{$field}'")->implode(', ');

        return <<<PHP

    protected \$appends = [{$appendsString}];

PHP;
    }

    /**
     * Build accessor methods.
     */
    protected function buildAccessors(array $definition): string
    {
        if (empty($definition['special_methods']['accessors'])) {
            return '';
        }

        $methods = [];
        foreach ($definition['special_methods']['accessors'] as $accessor) {
            $name = $accessor['name'];
            $returnType = $accessor['return_type'] ?? 'mixed';
            $description = $accessor['description'] ?? "Get the {$name} attribute.";

            if ($name === 'getBlocksAttribute') {
                $methods[] = <<<PHP

    /**
     * {$description}
     *
     * @return {$returnType}
     */
    public function getBlocksAttribute(): array
    {
        return collect(\$this->section)->map(function (array \$block) {
            if (isset(\$block['data']['media_id'])) {
                \$media = Media::find(\$block['data']['media_id']);
                \$block['data']['media_url'] = \$media?->url;
            }
            return \$block;
        })->all();
    }
PHP;
            }
        }

        return implode("\n", $methods);
    }

    /**
     * Build custom methods.
     */
    protected function buildCustomMethods(array $definition): string
    {
        if (empty($definition['special_methods']['custom_methods'])) {
            return '';
        }

        $methods = [];
        foreach ($definition['special_methods']['custom_methods'] as $method) {
            $name = $method['name'];
            $returnType = $method['return_type'] ?? 'mixed';
            $description = $method['description'] ?? "Custom method {$name}.";

            if ($name === 'childrenRecursive') {
                $methods[] = <<<PHP

    /**
     * {$description}
     *
     * @return {$returnType}
     */
    public function childrenRecursive(): HasMany
    {
        return \$this->children()->with('childrenRecursive');
    }
PHP;
            }
        }

        return implode("\n", $methods);
    }
}