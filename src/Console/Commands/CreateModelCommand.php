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
     * Base timestamp for generating migrations
     *
     * @var \Carbon\Carbon
     */
    protected $baseTimestamp;

    /**
     * Counter for migration ordering
     *
     * @var int
     */
    protected $migrationCounter = 0;

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
        $this->baseTimestamp = now();
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

        // Separate and order models properly
        $orderedModels = $this->orderModels($modelsToProcess);

        $generatedCount = 0;
        $migrationCount = 0;

        // Process models in dependency order
        foreach ($orderedModels as $modelName => $modelData) {
            $modelDefinition = $modelData['definition'];
            $isPivot = $modelData['is_pivot'];
            
            $this->info("Processing " . ($isPivot ? "pivot" : "regular") . " model: {$modelName}");
            
            if ($this->generateModelFile($modelName, $modelDefinition, $force)) {
                $generatedCount++;
            }
            
            if ($withMigration) {
                if ($this->generateMigrationFile($modelName, $modelDefinition, $isPivot)) {
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
     * Order models to ensure dependencies are processed first
     */
    protected function orderModels(array $models): array
    {
        $regularModels = [];
        $pivotModels = [];
        $ordered = [];

        // First pass: separate regular models from pivot models
        foreach ($models as $modelName => $modelDefinition) {
            $isPivot = $this->isPivotTable($modelName, $modelDefinition);
            
            if ($isPivot) {
                $pivotModels[$modelName] = [
                    'definition' => $modelDefinition,
                    'is_pivot' => true,
                    'dependencies' => $this->getPivotDependencies($modelName, $modelDefinition)
                ];
            } else {
                $regularModels[$modelName] = [
                    'definition' => $modelDefinition,
                    'is_pivot' => false,
                    'dependencies' => []
                ];
            }
        }

        // Add regular models first (in alphabetical order for consistency)
        ksort($regularModels);
        foreach ($regularModels as $modelName => $modelData) {
            $ordered[$modelName] = $modelData;
        }

        // Then add pivot models, ensuring their dependencies exist first
        $pivotModels = $this->sortPivotsByDependencies($pivotModels, array_keys($regularModels));
        foreach ($pivotModels as $modelName => $modelData) {
            $ordered[$modelName] = $modelData;
        }

        return $ordered;
    }

    /**
     * Get pivot table dependencies (related models)
     */
    protected function getPivotDependencies(string $modelName, array $definition): array
    {
        $dependencies = [];

        // Extract from relationships
        if (!empty($definition['relationships'])) {
            foreach ($definition['relationships'] as $relDef) {
                if (strtolower($relDef['type'] ?? '') === 'belongsto' && !empty($relDef['model'])) {
                    $dependencies[] = $relDef['model'];
                }
            }
        }

        // Extract from naming convention (e.g., user_role -> user, role)
        if (str_contains($modelName, '_')) {
            $parts = explode('_', $modelName);
            if (count($parts) >= 2) {
                // Take first and last parts as potential model names
                $dependencies[] = $parts[0];
                $dependencies[] = end($parts);
            }
        }

        return array_unique($dependencies);
    }

    /**
     * Sort pivot models by their dependencies
     */
    protected function sortPivotsByDependencies(array $pivotModels, array $availableModels): array
    {
        $sorted = [];
        $remaining = $pivotModels;

        // Simple sorting: pivots with fewer dependencies first
        uasort($remaining, function($a, $b) {
            return count($a['dependencies']) <=> count($b['dependencies']);
        });

        return $remaining;
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
        
        // Generate timestamp with guaranteed ordering
        $timestamp = $this->getNextMigrationTimestamp($isPivot);
        $migrationFileName = "{$timestamp}_create_{$tableName}_table.php";
        $migrationPath = database_path("migrations/{$migrationFileName}");
        
        // Check if migration already exists
        $existingMigrations = glob(database_path("migrations/*_create_{$tableName}_table.php"));
        if (!empty($existingMigrations) && !$this->option('force')) {
            $this->line("Migration for {$tableName} already exists. Skipping...");
            return false;
        }
        
        // Remove existing migrations if force option is used
        if (!empty($existingMigrations) && $this->option('force')) {
            foreach ($existingMigrations as $existingMigration) {
                $this->files->delete($existingMigration);
                $this->line("Removed existing migration: " . basename($existingMigration));
            }
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
     * Get next migration timestamp with guaranteed ordering
     */
    protected function getNextMigrationTimestamp(bool $isPivot = false): string
    {
        // Increment counter for each migration
        $this->migrationCounter++;
        
        // For pivot tables, add significant time offset to ensure they come after regular tables
        $offset = $isPivot ? 3600 + ($this->migrationCounter * 60) : $this->migrationCounter * 60; // 1 hour + minutes for pivots
        
        return $this->baseTimestamp->copy()->addSeconds($offset)->format('Y_m_d_His');
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
                $fields[] = $this->buildFieldDefinition($fieldName, $fieldDef, $isPivot);
            }
        }
        
        // Add foreign keys from relationships for belongsTo (if not already defined in fields)
        if (!empty($definition['relationships'])) {
            foreach ($definition['relationships'] as $relationName => $relDef) {
                if (strtolower($relDef['type'] ?? '') === 'belongsto') {
                    $foreignKey = $relDef['foreign_key'] ?? Str::snake($relationName) . '_id';
                    
                    // Only add if not already defined in fields
                    if (empty($definition['fields'][$foreignKey])) {
                        $relatedModel = $relDef['model'] ?? null;
                        if ($relatedModel) {
                            $relatedTable = $this->getTableName($relatedModel);
                            $fields[] = "            \$table->foreignId('{$foreignKey}')->constrained('{$relatedTable}')->cascadeOnDelete();";
                        } else {
                            $fields[] = "            \$table->foreignId('{$foreignKey}')->constrained()->cascadeOnDelete();";
                        }
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
    protected function buildFieldDefinition(string $fieldName, array $fieldDef, bool $isPivot = false): string
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
                // For pivot tables with foreign IDs, add proper constraints
                if ($isPivot && str_ends_with($fieldName, '_id')) {
                    $relatedTable = Str::plural(str_replace('_id', '', $fieldName));
                    $definition .= "foreignId('{$fieldName}')->constrained('{$relatedTable}')->cascadeOnDelete()";
                } else {
                    $definition .= "foreignId('{$fieldName}')";
                }
                break;
                
            default:
                $definition .= "string('{$fieldName}')";
        }
        
        // Add nullable (but not for foreign keys in pivot tables)
        if ($nullable && !($isPivot && str_ends_with($fieldName, '_id'))) {
            $definition .= "->nullable()";
        }
        
        // Add default value (but not for foreign keys)
        if ($default !== null && !str_ends_with($fieldName, '_id')) {
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

    // ... (rest of the methods remain the same as in your original code)
    // Including: generateModelFile, buildModelContent, buildUses, buildTraits, 
    // buildConstants, buildFillable, buildCasts, buildTranslatable, buildRelationships,
    // buildAppends, buildAccessors, buildCustomMethods
    
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

    // Add all your other existing methods here (buildModelContent, buildUses, etc.)
    // I'll include a few key ones to show the pattern:

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

    // Include all your other existing methods here...
    // (buildUses, buildTraits, buildConstants, buildFillable, buildCasts, 
    //  buildTranslatable, buildRelationships, buildAppends, buildAccessors, buildCustomMethods)
}