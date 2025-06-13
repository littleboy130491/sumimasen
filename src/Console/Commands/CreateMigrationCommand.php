<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Exception\ParseException; // Required for MigrationCreator
use Symfony\Component\Yaml\Yaml; // Added for easier collection handling

class CreateMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:migration:from-yaml
                            {yaml_file? : The path to the YAML definition file (defaults to schemas/models.yaml)}
                            {--model= : Specify a single model from the YAML to generate the migration for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate database migration(s) including pivot tables from a YAML definition file';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The migration creator instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance. Needed by MigrationCreator.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Stores unique pivot table definitions to avoid duplicates.
     * Format: ['pivot_table_name' => ['ModelA', 'ModelB']]
     *
     * @var array
     */
    protected $pivotTablesToCreate = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files, MigrationCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->creator = $creator;
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
        $this->pivotTablesToCreate = []; // Reset pivot tables list

        // --- First Pass: Generate Main Migrations & Collect Pivot Table Info ---
        foreach ($modelsToProcess as $modelName => $modelDefinition) {
            $this->info("Processing main table for model: {$modelName}");
            $generated = $this->generateModelMigration($modelName, $modelDefinition);
            if ($generated) {
                $generatedCount++;
            }
            // Collect pivot table info
            $this->collectPivotTables($modelName, $modelDefinition['relationships'] ?? []);
        }

        // --- Second Pass: Generate Pivot Table Migrations ---
        $this->info('Processing pivot tables...');
        foreach ($this->pivotTablesToCreate as $pivotTableName => $models) {
            $generated = $this->generatePivotMigration($pivotTableName, $models[0], $models[1]);
            if ($generated) {
                $generatedCount++;
            }
        }

        // Regenerate composer autoload dump only if new files were created.
        if ($generatedCount > 0) {
            $this->composer->dumpAutoloads();
            $this->info('Composer autoload files regenerated.');
        } else {
            $this->info('No new migration files were generated.');
        }

        $this->info('Migration generation process completed.');

        return 0;
    }

    /**
     * Collects information about required pivot tables from belongsToMany relationships.
     *
     * @param  string  $modelName  The name of the current model being processed.
     * @param  array  $relationships  The relationships defined for the current model.
     */
    protected function collectPivotTables(string $modelName, array $relationships): void
    {
        foreach ($relationships as $relationName => $relationDef) {
            $type = $relationDef['type'] ?? null;
            $relatedModel = $relationDef['model'] ?? null;

            if (strtolower($type) === 'belongstomany' && $relatedModel) {
                // Determine conventional pivot table name by sorting model names alphabetically
                $models = collect([$modelName, $relatedModel])
                    ->map(fn ($name) => Str::snake($name)) // Convert to snake_case
                    ->sort() // Sort alphabetically
                    ->all();

                $pivotTableName = implode('_', $models);

                // Store the relationship if not already collected
                if (! isset($this->pivotTablesToCreate[$pivotTableName])) {
                    $this->pivotTablesToCreate[$pivotTableName] = [$modelName, $relatedModel]; // Store original model names
                    $this->line(" -> Found requirement for pivot table: {$pivotTableName} ({$modelName} <=> {$relatedModel})");
                }
            }
        }
    }

    /**
     * Generate a migration file for a given model's main table.
     * Returns true if a migration was generated, false otherwise.
     */
    protected function generateModelMigration(string $modelName, array $definition): bool
    {
        // Determine table name (e.g., Post -> posts, UserProfile -> user_profiles)
        $tableName = Str::snake(Str::pluralStudly($modelName));
        $migrationBaseName = "create_{$tableName}_table";
        $migrationPathDir = database_path('migrations');
        $fieldsDefinition = $definition['fields'] ?? []; // Get fields definition

        // --- Check if a migration for this table already exists ---
        if ($this->migrationExists($migrationPathDir, $migrationBaseName)) {
            $this->warn("A migration for table '{$tableName}' seems to exist already.");
            if (! $this->confirm("Create a new migration file for '{$tableName}' anyway?", false)) {
                $this->line("Skipping main table generation for model: {$modelName}");

                return false; // Indicate no migration was generated
            }
            $this->line("Proceeding to create a new migration file for {$tableName}...");
        }
        // --- End Check ---

        // --- Build the schema string for the 'up' method ---
        $schemaUp = collect($fieldsDefinition) // Use fieldsDefinition here
            ->map(function ($fieldDef, $fieldName) {
                return $this->buildFieldSchema($fieldName, $fieldDef);
            })
            ->prepend('$table->id();') // Add primary key
            // Pass fieldsDefinition to buildRelationshipSchema
            ->merge($this->buildRelationshipSchema($definition['relationships'] ?? [], $fieldsDefinition))
            ->push('$table->timestamps();') // Add timestamps
            ->when(isset($definition['traits']) && in_array('Illuminate\Database\Eloquent\SoftDeletes', $definition['traits']), function ($collection) {
                return $collection->push('$table->softDeletes();'); // Add soft deletes if trait exists
            })
            ->map(fn ($line) => "            {$line}") // Indent lines
            ->implode("\n");

        // --- Build the schema string for the 'down' method ---
        $schemaDown = "Schema::dropIfExists('{$tableName}');";

        try {
            // Use Laravel's MigrationCreator to create the file
            $migrationPath = $this->creator->create(
                $migrationBaseName, // e.g., create_posts_table (creator adds timestamp)
                $migrationPathDir,
                $tableName, // Table name for the creator
                true // Specify that the table should be created
            );

            // Manually update the created migration file content
            $this->replaceSchemaInMigration($migrationPath, $schemaUp, $schemaDown);

            $this->line("<info>Created Migration:</info> {$migrationPath}");

            return true; // Indicate migration was generated

        } catch (\Exception $e) {
            $this->error("Failed to create migration for {$modelName}: ".$e->getMessage());

            return false; // Indicate no migration was generated
        }
    }

    /**
     * Generate a migration file for a pivot table.
     * Returns true if a migration was generated, false otherwise.
     *
     * @param  string  $pivotTableName  The calculated name of the pivot table.
     * @param  string  $modelA  Name of the first related model.
     * @param  string  $modelB  Name of the second related model.
     */
    protected function generatePivotMigration(string $pivotTableName, string $modelA, string $modelB): bool
    {
        $migrationBaseName = "create_{$pivotTableName}_table";
        $migrationPathDir = database_path('migrations');

        // --- Check if a migration for this pivot table already exists ---
        if ($this->migrationExists($migrationPathDir, $migrationBaseName)) {
            $this->warn("A migration for pivot table '{$pivotTableName}' seems to exist already.");
            if (! $this->confirm("Create a new migration file for '{$pivotTableName}' anyway?", false)) {
                $this->line("Skipping pivot table generation for: {$pivotTableName}");

                return false; // Indicate no migration was generated
            }
            $this->line("Proceeding to create a new migration file for {$pivotTableName}...");
        }
        // --- End Check ---

        // Determine foreign keys and related table names
        $foreignKeyA = Str::snake($modelA).'_id';
        $relatedTableA = Str::snake(Str::pluralStudly($modelA));
        $foreignKeyB = Str::snake($modelB).'_id';
        $relatedTableB = Str::snake(Str::pluralStudly($modelB));

        // --- Build the schema string for the 'up' method ---
        // Consider adding onDelete('cascade') or making it configurable in YAML
        $schemaUp = collect([
            "\$table->foreignId('{$foreignKeyA}')->constrained('{$relatedTableA}')->cascadeOnDelete();",
            "\$table->foreignId('{$foreignKeyB}')->constrained('{$relatedTableB}')->cascadeOnDelete();",
            "\$table->primary(['{$foreignKeyA}', '{$foreignKeyB}']);", // Composite primary key
        ])->map(fn ($line) => "            {$line}") // Indent lines
            ->implode("\n");

        // --- Build the schema string for the 'down' method ---
        $schemaDown = "Schema::dropIfExists('{$pivotTableName}');";

        try {
            // Use Laravel's MigrationCreator to create the file
            $migrationPath = $this->creator->create(
                $migrationBaseName,
                $migrationPathDir,
                $pivotTableName, // Pass pivot table name
                true // Specify that the table should be created
            );

            // Manually update the created migration file content
            $this->replaceSchemaInMigration($migrationPath, $schemaUp, $schemaDown);

            $this->line("<info>Created Pivot Migration:</info> {$migrationPath}");

            return true; // Indicate migration was generated

        } catch (\Exception $e) {
            $this->error("Failed to create pivot migration for {$pivotTableName}: ".$e->getMessage());

            return false; // Indicate no migration was generated
        }
    }

    /**
     * Checks if a migration file matching a base name already exists.
     *
     * @param  string  $directory  The directory to search in.
     * @param  string  $baseName  The base name of the migration (e.g., create_users_table).
     */
    protected function migrationExists(string $directory, string $baseName): bool
    {
        if (! $this->files->isDirectory($directory)) {
            return false;
        }

        foreach ($this->files->glob($directory.'/*_'.$baseName.'.php') as $file) {
            return true; // Found at least one match
        }

        return false;
    }

    /**
     * Build the Blueprint schema string for a single field.
     */
    protected function buildFieldSchema(string $fieldName, array $fieldDef): string
    {
        $type = $fieldDef['type'] ?? 'string'; // Default to string if not specified
        $schema = '$table';

        // Map YAML types to Blueprint methods
        switch (strtolower($type)) {
            case 'string':
                $length = $fieldDef['length'] ?? 255;
                $schema .= "->string('{$fieldName}', {$length})";
                break;
            case 'text':
                $schema .= "->text('{$fieldName}')";
                break;
            case 'longtext':
                $schema .= "->longText('{$fieldName}')";
                break;
            case 'int':
            case 'integer':
                $schema .= "->integer('{$fieldName}')";
                break;
            case 'bigint':
                $schema .= "->bigInteger('{$fieldName}')";
                break;
            case 'tinyint':
                $schema .= "->tinyInteger('{$fieldName}')";
                break;
            case 'float':
                $schema .= "->float('{$fieldName}')";
                break;
            case 'double':
                $schema .= "->double('{$fieldName}')";
                break;
            case 'decimal':
                $precision = $fieldDef['precision'] ?? 8;
                $scale = $fieldDef['scale'] ?? 2;
                $schema .= "->decimal('{$fieldName}', {$precision}, {$scale})";
                break;
            case 'bool':
            case 'boolean':
                $schema .= "->boolean('{$fieldName}')";
                break;
            case 'date':
                $schema .= "->date('{$fieldName}')";
                break;
            case 'datetime':
            case 'timestamp':
                $schema .= "->timestamp('{$fieldName}')"; // Common Laravel default
                break;
            case 'datetimetz':
            case 'timestamptz':
                $schema .= "->timestampTz('{$fieldName}')";
                break;
            case 'time':
                $schema .= "->time('{$fieldName}')";
                break;
            case 'json':
                $schema .= "->json('{$fieldName}')";
                break;
            case 'uuid':
                $schema .= "->uuid('{$fieldName}')";
                break;
            case 'enum':
                $allowed = "['".implode("', '", $fieldDef['enum'] ?? [])."']";
                $schema .= "->enum('{$fieldName}', {$allowed})";
                break;
                // Add more type mappings as needed
            default:
                $this->warn("Unsupported field type '{$type}' for field '{$fieldName}'. Defaulting to string.");
                $schema .= "->string('{$fieldName}')";
        }

        // Add modifiers
        // Use nullable: false as the check for non-nullable fields
        if ($fieldDef['nullable'] ?? true) { // Default to nullable if 'nullable' key is missing
            if (isset($fieldDef['nullable']) && $fieldDef['nullable'] === true) {
                $schema .= '->nullable()';
            }
            // If nullable key exists and is false, do nothing (it's not nullable by default)
        } else {
            // Explicitly nullable: false means it's not nullable. No ->nullable() needed.
        }

        if ($fieldDef['unique'] ?? false) {
            $schema .= '->unique()';
        }

        if (isset($fieldDef['default'])) {
            $default = $fieldDef['default'];
            if (is_string($default)) {
                $schema .= "->default('{$default}')";
            } elseif (is_bool($default)) {
                $schema .= '->default('.($default ? 'true' : 'false').')';
            } elseif (is_null($default)) {
                $schema .= '->default(null)'; // Should usually be combined with nullable()
            } else { // Numeric
                $schema .= "->default({$default})";
            }
        }

        if ($fieldDef['index'] ?? false) {
            // Avoid adding index if unique is already set, as unique constraints usually have an index
            if (! ($fieldDef['unique'] ?? false)) {
                $schema .= '->index()';
            }
        }

        if ($fieldDef['unsigned'] ?? false) {
            // Useful for integer types used as foreign keys (though foreignId handles this)
            $schema .= '->unsigned()';
        }

        // Add comment if provided
        if (isset($fieldDef['comment'])) {
            $comment = addslashes($fieldDef['comment']);
            $schema .= "->comment('{$comment}')";
        }

        return $schema.';';
    }

    /**
     * Build the Blueprint schema strings for foreign keys based on relationships.
     * Only handles 'belongsTo' for adding columns/constraints to *this* table's migration.
     *
     * @param  array  $relationships  The relationships array from YAML.
     * @param  array  $fieldsDefinition  The fields array from YAML for the current model.
     */
    protected function buildRelationshipSchema(array $relationships, array $fieldsDefinition): array
    {
        $schemaLines = [];
        foreach ($relationships as $relationName => $relationDef) {
            $type = $relationDef['type'] ?? null;
            $relatedModel = $relationDef['model'] ?? null;

            // Only process belongsTo here. belongsToMany is handled separately.
            if (strtolower($type) === 'belongsto' && $relatedModel) {
                // Infer or get foreign key column name
                $foreignKey = $relationDef['foreign_key'] ?? Str::snake($relationName).'_id';
                // Infer related table name (e.g., User -> users) - assumes standard naming
                $relatedTable = Str::snake(Str::pluralStudly($relatedModel));
                // Infer primary key on related table (usually 'id')
                $relatedKey = $relationDef['related_key'] ?? 'id';

                $line = '';

                // *** FIX: Check if the foreign key column was already defined in 'fields' ***
                if (! array_key_exists($foreignKey, $fieldsDefinition)) {
                    // If NOT defined in fields, create the column using foreignId()
                    $line = "\$table->foreignId('{$foreignKey}')";

                    // Handle nullability for the column itself if defined in relationship
                    if ($relationDef['nullable'] ?? false) {
                        $line .= '->nullable()';
                    }
                    // Add constraint directly after foreignId
                    $line .= "->constrained('{$relatedTable}', '{$relatedKey}')"; // Constrain to inferred table/key

                } else {
                    // If defined in fields, just add the constraint using foreign()
                    $line = "\$table->foreign('{$foreignKey}')"
                        ."->references('{$relatedKey}')->on('{$relatedTable}')";
                }

                // Add cascade options if specified in YAML (e.g., onDelete: cascade)
                // These apply whether using foreignId() or foreign()
                if (isset($relationDef['onDelete'])) {
                    $action = $relationDef['onDelete'];
                    // Map common actions to Blueprint methods
                    if ($action === 'cascade') {
                        $line .= '->cascadeOnDelete()';
                    } elseif ($action === 'restrict') {
                        $line .= '->restrictOnDelete()';
                    } elseif ($action === 'set null') {
                        $line .= '->nullOnDelete()';
                    } // Column must be nullable
                    else {
                        $line .= "->onDelete('{$action}')";
                    } // Raw action
                }
                if (isset($relationDef['onUpdate'])) {
                    $action = $relationDef['onUpdate'];
                    if ($action === 'cascade') {
                        $line .= '->cascadeOnUpdate()';
                    } elseif ($action === 'restrict') {
                        $line .= '->restrictOnUpdate()';
                    } else {
                        $line .= "->onUpdate('{$action}')";
                    }
                }

                $schemaLines[] = $line.';';
            }
        }

        return $schemaLines;
    }

    /**
     * Replace placeholder schema definitions in the newly created migration file.
     *
     * @param  string  $migrationPath  The full path to the migration file.
     * @param  string  $schemaUp  The schema string for the up() method.
     * @param  string  $schemaDown  The schema string for the down() method.
     * @return void
     */
    protected function replaceSchemaInMigration(string $migrationPath, string $schemaUp, string $schemaDown)
    {
        $content = $this->files->get($migrationPath);

        // Replace the default up() method's Schema::create content
        $upPlaceholder = '/Schema::create\s*\(\s*([\'"])(?<table>.*?)\1\s*,\s*function\s*\(\s*Blueprint\s*\$table\s*\)\s*\{[\s\S]*?\}\s*\);/s';
        $newUpContent = "Schema::create('$2', function (Blueprint \$table) {\n{$schemaUp}\n        });";
        $content = preg_replace($upPlaceholder, $newUpContent, $content, 1, $countUp);

        if ($countUp === 0) {
            $this->warn("Could not automatically replace schema in 'up' method for migration: {$migrationPath}. Manual review might be needed.");
        }

        // Replace the default down() method's Schema::dropIfExists content
        $downPlaceholder = '/Schema::dropIfExists\s*\(\s*([\'"])(?<table>.*?)\1\s*\);/';
        $newDownContent = $schemaDown;
        $content = preg_replace($downPlaceholder, $newDownContent, $content, 1, $countDown);

        if ($countDown === 0) {
            $this->warn("Could not automatically replace schema in 'down' method for migration: {$migrationPath}. Manual review might be needed.");
        }

        $this->files->put($migrationPath, $content);
    }
}
