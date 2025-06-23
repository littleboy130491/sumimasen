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
                            {--force : Overwrite existing model files without asking}
                            {--with-migration : Generate migration files along with models}';

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
        $withMigration = $this->option('with-migration');

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

        if ($specificModel) {
            if (! isset($modelsToProcess[$specificModel])) {
                $this->error("Model '{$specificModel}' not found in the YAML file.");
                return 1;
            }
            $modelsToProcess = [$specificModel => $modelsToProcess[$specificModel]];
        }

        // Separate pivot tables from regular models
        $regularModels = [];
        $pivotModels = [];

        foreach ($modelsToProcess as $modelName => $modelDefinition) {
            if ($this->isPivotTable($modelName, $modelDefinition)) {
                $pivotModels[$modelName] = $modelDefinition;
            } else {
                $regularModels[$modelName] = $modelDefinition;
            }
        }

        $generatedCount = 0;
        $migrationCount = 0;

        // Process regular models first
        foreach ($regularModels as $modelName => $modelDefinition) {
            $this->info("Processing regular model: {$modelName}");
            if ($this->generateModelFile($modelName, $modelDefinition, $force)) {
                $generatedCount++;
            }
            
            if ($withMigration) {
                if ($this->generateMigrationFile($modelName, $modelDefinition, false)) {
                    $migrationCount++;
                }
            }
        }

        // Add a small delay to ensure different timestamps for pivot tables
        if (!empty($pivotModels) && !empty($regularModels)) {
            $this->info("Waiting to ensure proper migration ordering...");
            sleep(2); // 2 seconds delay
        }

        // Process pivot models after regular models
        foreach ($pivotModels as $modelName => $modelDefinition) {
            $this->info("Processing pivot model: {$modelName}");
            if ($this->generateModelFile($modelName, $modelDefinition, $force)) {
                $generatedCount++;
            }
            
            if ($withMigration) {
                if ($this->generateMigrationFile($modelName, $modelDefinition, true)) {
                    $migrationCount++;
                }
            }
        }

        if ($generatedCount > 0) {
            $this->composer->dumpAutoloads();
            $this->info('Composer autoload files regenerated.');
        } else {
            $this->info('No new model files were generated.');
        }

        if ($withMigration && $migrationCount > 0) {
            $this->info("Generated {$migrationCount} migration files with proper ordering.");
        }

        $this->info('Model generation process completed.');
        return 0;
    }

    /**
     * Determine if a model is a pivot table based on naming convention or relationships
     */
    protected function isPivotTable(string $modelName, array $definition): bool
    {
        // Check if model name contains underscore (common pivot naming convention)
        if (str_contains($modelName, '_')) {
            // Additional check: if it has exactly 2 parts and both might be model names
            $parts = explode('_', $modelName);
            if (count($parts) >= 2) {
                return true;
            }
        }

        // Check if explicitly marked as pivot in YAML
        if (isset($definition['is_pivot']) && $definition['is_pivot']) {
            return true;
        }

        // Check if model only has belongsTo relationships to 2 or more models
        if (!empty($definition['relationships'])) {
            $belongsToCount = 0;
            foreach ($definition['relationships'] as $relDef) {
                if (strtolower($relDef['type'] ?? '') === 'belongsto') {
                    $belongsToCount++;
                }
            }
            
            if ($belongsToCount >= 2) {
                return true;
            }
        }

        // Check for pivot-specific field patterns
        if (!empty($definition['fields'])) {
            $fieldNames = array_keys($definition['fields']);
            $foreignKeyCount = 0;
            
            foreach ($fieldNames as $fieldName) {
                if (str_ends_with($fieldName, '_id')) {
                    $foreignKeyCount++;
                }
            }
            
            // If has 2 or more foreign keys and few other fields, likely a pivot
            if ($foreignKeyCount >= 2 && count($fieldNames) <= $foreignKeyCount + 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate migration file along with model
     */
    protected function generateMigrationFile(string $modelName, array $definition, bool $isPivot = false): bool
    {
        $tableName = $this->getTableName($modelName);
        $className = 'Create' . Str::studly($tableName) . 'Table';
        
        // Generate timestamp with extra seconds for pivot tables
        $baseTime = now();
        if ($isPivot) {
            $baseTime = $baseTime->addSeconds(rand(10, 30)); // Add random delay for pivots
        }
        
        $timestamp = $baseTime->format('Y_m_d_His');
        $migrationFileName = "{$timestamp}_create_{$tableName}_table.php";
        $migrationPath = database_path("migrations/{$migrationFileName}");
        
        // Check if migration already exists
        $existingMigrations = glob(database_path("migrations/*_create_{$tableName}_table.php"));
        if (!empty($existingMigrations) && !$this->option('force')) {
            $this->line("Migration for {$tableName} already exists. Skipping...");
            return false;
        }
        
        // Generate migration content
        $migrationContent = $this->buildMigrationContent($className, $tableName, $definition, $isPivot);
        
        if ($this->files->put($migrationPath, $migrationContent) !== false) {
            $this->line("<info>Created Migration:</info> {$migrationPath}");
            return true;
        } else {
            $this->error("Failed to create migration: {$migrationPath}");
            return false;
        }
    }

    /**
     * Get table name from model name
     */
    protected function getTableName(string $modelName): string
    {
        // For pivot tables, keep the exact name
        if (str_contains($modelName, '_')) {
            return $modelName;
        }
        
        // For regular models, pluralize
        return Str::snake(Str::pluralStudly($modelName));
    }

    /**
     * Build migration file content
     */
    protected function buildMigrationContent(string $className, string $tableName, array $definition, bool $isPivot = false): string
    {
        $fields = $this->buildMigrationFields($definition, $isPivot);
        $indexes = $this->buildMigrationIndexes($definition, $isPivot);
        
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
{$fields}
{$indexes}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

PHP;
    }

    /**
     * Build migration fields
     */
    protected function buildMigrationFields(array $definition, bool $isPivot = false): string
    {
        $fields = [];
        
        // Add ID field for non-pivot tables
        if (!$isPivot) {
            $fields[] = "            \$table->id();";
        }
        
        // Add defined fields
        if (!empty($definition['fields'])) {
            foreach ($definition['fields'] as $fieldName => $fieldDef) {
                $fields[] = $this->buildFieldDefinition($fieldName, $fieldDef);
            }
        }
        
        // Add foreign keys from relationships for belongsTo
        if (!empty($definition['relationships'])) {
            foreach ($definition['relationships'] as $relationName => $relDef) {
                if (strtolower($relDef['type'] ?? '') === 'belongsto') {
                    $foreignKey = $relDef['foreign_key'] ?? Str::snake($relationName) . '_id';
                    
                    // Only add if not already defined in fields
                    if (empty($definition['fields'][$foreignKey])) {
                        $fields[] = "            \$table->foreignId('{$foreignKey}')->constrained()->cascadeOnDelete();";
                    }
                }
            }
        }
        
        // Add timestamps for non-pivot tables
        if (!$isPivot) {
            $fields[] = "            \$table->timestamps();";
        }
        
        return implode("\n", $fields);
    }

    /**
     * Build field definition for migration
     */
    protected function buildFieldDefinition(string $fieldName, array $fieldDef): string
    {
        $type = strtolower($fieldDef['type'] ?? 'string');
        $nullable = $fieldDef['nullable'] ?? false;
        $default = $fieldDef['default'] ?? null;
        
        $definition = "            \$table->";
        
        switch ($type) {
            case 'string':
                $length = $fieldDef['length'] ?? null;
                if ($length) {
                    $definition .= "string('{$fieldName}', {$length})";
                } else {
                    $definition .= "string('{$fieldName}')";
                }
                break;
                
            case 'text':
                $definition .= "text('{$fieldName}')";
                break;
                
            case 'longtext':
                $definition .= "longText('{$fieldName}')";
                break;
                
            case 'int':
            case 'integer':
                $definition .= "integer('{$fieldName}')";
                break;
                
            case 'bigint':
                $definition .= "bigInteger('{$fieldName}')";
                break;
                
            case 'tinyint':
                $definition .= "tinyInteger('{$fieldName}')";
                break;
                
            case 'bool':
            case 'boolean':
                $definition .= "boolean('{$fieldName}')";
                break;
                
            case 'date':
                $definition .= "date('{$fieldName}')";
                break;
                
            case 'datetime':
            case 'timestamp':
                $definition .= "timestamp('{$fieldName}')";
                break;
                
            case 'json':
                $definition .= "json('{$fieldName}')";
                break;
                
            case 'decimal':
                $precision = $fieldDef['precision'] ?? 8;
                $scale = $fieldDef['scale'] ?? 2;
                $definition .= "decimal('{$fieldName}', {$precision}, {$scale})";
                break;
                
            case 'float':
                $definition .= "float('{$fieldName}')";
                break;
                
            case 'enum':
                if (!empty($fieldDef['enum'])) {
                    $enumValues = collect($fieldDef['enum'])
                        ->map(fn($value) => "'{$value}'")
                        ->implode(', ');
                    $definition .= "enum('{$fieldName}', [{$enumValues}])";
                } else {
                    $definition .= "string('{$fieldName}')";
                }
                break;
                
            case 'foreignid':
                $definition .= "foreignId('{$fieldName}')";
                break;
                
            default:
                $definition .= "string('{$fieldName}')";
        }
        
        // Add nullable
        if ($nullable) {
            $definition .= "->nullable()";
        }
        
        // Add default value
        if ($default !== null) {
            if (is_string($default)) {
                $definition .= "->default('{$default}')";
            } elseif (is_bool($default)) {
                $definition .= "->default(" . ($default ? 'true' : 'false') . ")";
            } else {
                $definition .= "->default({$default})";
            }
        }
        
        return $definition . ";";
    }

    /**
     * Build migration indexes
     */
    protected function buildMigrationIndexes(array $definition, bool $isPivot = false): string
    {
        $indexes = [];
        
        // For pivot tables, create composite primary key
        if ($isPivot && !empty($definition['fields'])) {
            $foreignKeys = [];
            foreach ($definition['fields'] as $fieldName => $fieldDef) {
                if (str_ends_with($fieldName, '_id')) {
                    $foreignKeys[] = $fieldName;
                }
            }
            
            if (count($foreignKeys) >= 2) {
                $keyString = "'" . implode("', '", $foreignKeys) . "'";
                $indexes[] = "            \$table->primary([{$keyString}]);";
            }
        }
        
        // Add custom indexes if defined
        if (!empty($definition['indexes'])) {
            foreach ($definition['indexes'] as $index) {
                $fields = is_array($index['fields']) ? $index['fields'] : [$index['fields']];
                $fieldString = "'" . implode("', '", $fields) . "'";
                
                switch ($index['type'] ?? 'index') {
                    case 'unique':
                        $indexes[] = "            \$table->unique([{$fieldString}]);";
                        break;
                    case 'index':
                    default:
                        $indexes[] = "            \$table->index([{$fieldString}]);";
                        break;
                }
            }
        }
        
        return empty($indexes) ? '' : "\n" . implode("\n", $indexes);
    }

    /**
     * Generate the Eloquent model file.
     *
     * @return bool Returns true if the file was generated, false otherwise.
     */
    protected function generateModelFile(string $modelName, array $definition, bool $force): bool
    {
        // Define namespace here so it's available for buildModelContent
        $namespace = 'App\\Models'; // Assuming default App\Models namespace
        $className = Str::studly($modelName); // Ensure PascalCase
        $filePath = app_path("Models/{$className}.php"); // Assumes default app/Models path

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
        // Pass namespace and className down to buildUses
        $uses = $this->buildUses($namespace, $className, $definition);
        $traits = $this->buildTraits($definition);
        $constants = $this->buildConstants($definition); // <-- Build constants
        $fillable = $this->buildFillable($definition);
        $casts = $this->buildCasts($definition); // Build casts normally
        $translatable = $this->buildTranslatable($definition);
        $appends = $this->buildAppends($definition);
        $accessors = $this->buildAccessors($definition);
        $customMethods = $this->buildCustomMethods($definition);
        $relationships = $this->buildRelationships($namespace, $definition); // Pass namespace

        // Basic template for the model file
        // Added {$constants} placeholder
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

        $relationshipTypes = []; // Store relationship types to avoid duplicate imports
        $relatedModelClasses = []; // Store related model classes to avoid duplicate imports

        if (! empty($definition['traits'])) {
            foreach ($definition['traits'] as $trait) {
                // Add the full trait namespace
                $uses[] = $trait;
            }
        }

        // Add specific imports based on fields
        if (! empty($definition['fields'])) {
            foreach ($definition['fields'] as $fieldName => $fieldDef) {
                if ($fieldName === 'featured_image') {
                    $uses[] = 'Awcodes\Curator\Models\Media';
                    $relationshipTypes[] = 'Illuminate\Database\Eloquent\Relations\BelongsTo'; // Add BelongsTo for featuredImage
                }

                // Note: We don't import enum classes since we use fully qualified names
            }
        }

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
                            // Add more relationship types if needed
                    }
                }

                // Add related model namespace
                if ($relatedModel) {
                    // Basic assumption: related models are in the same namespace
                    // You might need a more complex lookup if models are in different namespaces
                    // Ensure we don't add the current model's namespace if it's self-referencing
                    $relatedModelFqn = $namespace.'\\'.Str::studly($relatedModel);
                    // Extract current class name from namespace for comparison
                    $currentClassName = Str::afterLast($namespace.'\\'.Str::studly(basename(str_replace('\\', '/', $namespace))), '\\');
                    $relatedClassName = Str::afterLast($relatedModelFqn, '\\');

                    if ($relatedClassName !== $currentClassName) {
                        $relatedModelClasses[] = $relatedModelFqn;
                    }
                }
            }
        }

        // Merge all uses and keep unique
        $uses = array_unique(array_merge($uses, $relationshipTypes, $relatedModelClasses));

        // Explicitly remove the current model's namespace and class from uses
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
        $traits = ['HasFactory']; // Always include HasFactory by default?
        if (! empty($definition['traits'])) {
            foreach ($definition['traits'] as $trait) {
                // Get the base name of the trait (e.g., SoftDeletes)
                $traits[] = class_basename($trait);
            }
        }

        $traits = array_unique($traits);

        if (empty($traits)) {
            return '';
        }

        // Combine traits on one line if multiple exist
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
            // Only generate constants for enum fields that don't have enum_class specified
            if (
                strtolower($fieldDef['type'] ?? '') === 'enum' &&
                ! empty($fieldDef['enum']) &&
                is_array($fieldDef['enum']) &&
                empty($fieldDef['enum_class'])
            ) {

                // Generate constant name, e.g., status -> STATUS_OPTIONS
                $constantName = Str::upper(Str::snake($fieldName)).'_OPTIONS';

                // Generate array string, e.g., ['draft' => 'Draft', 'published' => 'Published']
                $optionsArray = collect($fieldDef['enum'])
                    ->mapWithKeys(function ($value) {
                        // Use Str::title or Str::ucfirst for the label
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

        return implode("\n", $constants)."\n"; // Add extra newline for spacing
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

        // Add inferred foreign keys from 'belongsTo' relationships ONLY if not already in fields
        if (! empty($definition['relationships'])) {
            foreach ($definition['relationships'] as $relationName => $relDef) {
                if (strtolower($relDef['type'] ?? '') === 'belongsto') {
                    $foreignKey = $relDef['foreign_key'] ?? Str::snake($relationName).'_id'; // Use explicit or infer
                    // Add only if not already defined in fields
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
        {$fillableString}
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
                    // Only cast if not primary key (usually 'id')
                    if ($fieldName !== 'id') { // Simple check, might need refinement
                        $casts[$fieldName] = 'integer';
                    }
                    break;
                case 'float':
                case 'double':
                case 'decimal':
                    $casts[$fieldName] = 'float'; // Or 'decimal:<precision>' if needed
                    break;
                case 'date':
                    $casts[$fieldName] = 'date';
                    break;
                case 'datetime':
                case 'timestamp':
                case 'datetimetz':
                case 'timestamptz':
                    $casts[$fieldName] = 'datetime'; // Or 'immutable_datetime'
                    break;
                case 'json':
                    // Cast JSON fields as array, with special handling for translatable fields
                    if (! $isTranslatable || $fieldName === 'section') {
                        // Non-translatable JSON fields or special case for 'section' field
                        $casts[$fieldName] = 'array';
                    }
                    // Translatable JSON fields (title, slug, content, excerpt) are handled by Spatie Translatable
                    break;
                case 'enum':
                    // Check if enum_class is specified
                    if (! empty($fieldDef['enum_class'])) {
                        $enumClass = $fieldDef['enum_class'];
                        // Use fully qualified name with leading backslash
                        $casts[$fieldName] = '\\'.$enumClass.'::class';
                    } else {
                        // Basic string cast for backward compatibility
                        $casts[$fieldName] = 'string';
                    }
                    break;
            }
        }

        if (empty($casts)) {
            return '';
        }

        $castsString = collect($casts)
            ->map(function ($castType, $field) {
                // Don't quote enum class references
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
        {$castsString}
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
            return ''; // No property needed if nothing is translatable
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
        {$translatableString}
    ];

PHP;
    }

    /**
     * Build the relationship method definitions.
     *
     * @param  string  $namespace  The namespace of the current model.
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
                $foreignKey = $relDef['foreign_key'] ?? null; // Get potential foreign key
                // Add other potential keys here if needed (ownerKey, localKey, etc.)

                if (! $type) {
                    continue; // Skip if type is missing
                }

                // For morphTo relationships, we don't need a model
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
                    continue; // Skip if model is missing for non-morphTo relationships
                }

                $relatedModelClass = Str::studly($relatedModel); // Ensure PascalCase
                $relationshipType = Str::studly($type); // e.g., BelongsTo, BelongsToMany - Get Class name for return type hint
                $relationshipMethod = Str::camel($type); // e.g., belongsTo, belongsToMany - Get method name for the call

                // Construct arguments string
                $arguments = ["{$relatedModelClass}::class"];

                // Handle special cases for different relationship types
                if (strtolower($type) === 'morphmany') {
                    $morphName = $relDef['name'] ?? Str::snake($methodName);
                    $arguments = ["{$relatedModelClass}::class", "'{$morphName}'"];
                } else {
                    // Add foreign_key as second argument if present
                    if ($foreignKey) {
                        $arguments[] = "'{$foreignKey}'";
                    }
                }
                // Add logic here for other keys (ownerKey, localKey, pivot keys etc.) if defined in YAML

                $argumentsString = implode(', ', $arguments);

                $methods[] = <<<PHP

    /**
     * Define the {$methodName} relationship.
     */
    public function {$methodName}(): {$relationshipType}
    {
        // Use the base class name for the ::class constant
        // Add foreign key argument if specified in YAML
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
                // Special handling for Page model blocks accessor
                $methods[] = <<<PHP

    /**
     * {$description}
     *
     * @return {$returnType}
     */
    public function getBlocksAttribute(): array
    {
        return collect(\$this->section)->map(function (array \$block) {
            // if this block has an "media" key, fetch its URL
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
                // Special handling for Comment model recursive children
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