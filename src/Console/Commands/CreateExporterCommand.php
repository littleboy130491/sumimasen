<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class CreateExporterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:exporter:from-yaml
                            {yaml_file? : The path to the YAML definition file (defaults to schemas/models.yaml)}
                            {--model= : Specify a single model from the YAML to generate the exporter for}
                            {--force : Overwrite existing exporter files without asking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Filament Exporter class(es) from a YAML definition file';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
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
            $this->info("Processing model for exporter: {$modelName}");
            if ($this->generateExporterFile($modelName, $modelDefinition, $force)) {
                $generatedCount++;
            }
        }

        $this->info('Exporter generation process completed.');

        return 0;
    }

    /**
     * Generate the Filament Exporter file.
     *
     * @return bool Returns true if the file was generated, false otherwise.
     */
    protected function generateExporterFile(string $modelName, array $definition, bool $force): bool
    {
        $className = Str::studly($modelName).'Exporter';
        $filePath = app_path("Filament/Exports/{$className}.php"); // Assumes app/Filament/Exports path

        // Check if file exists and prompt for overwrite unless --force is used
        if ($this->files->exists($filePath) && ! $force) {
            if (! $this->confirm("Exporter file [{$filePath}] already exists. Overwrite?", false)) {
                $this->line("Skipping generation for exporter: {$className}");

                return false;
            }
        }

        // Ensure the directory exists
        $directoryPath = dirname($filePath);
        if (! $this->files->isDirectory($directoryPath)) {
            $this->files->makeDirectory($directoryPath, 0755, true);
        }

        // Build the exporter content
        $content = $this->buildExporterContent($modelName, $className, $definition);

        // Write the file
        if ($this->files->put($filePath, $content) !== false) {
            $this->line("<info>Created Exporter:</info> {$filePath}");

            return true;
        } else {
            $this->error("Failed to write exporter file: {$filePath}");

            return false;
        }
    }

    /**
     * Build the full content of the Exporter file.
     */
    protected function buildExporterContent(string $modelName, string $className, array $definition): string
    {
        $modelClass = 'Littleboy130491\Sumimasen\\Models\\'.Str::studly($modelName);
        $modelVariableName = Str::camel($modelName);
        $fields = $definition['fields'] ?? [];
        $relationships = $definition['relationships'] ?? [];

        $exportColumns = collect($fields)
            ->filter(function ($fieldDef, $fieldName) use ($relationships) {
                // Filter out foreign key fields if a corresponding belongsTo relationship exists
                foreach ($relationships as $relName => $relDef) {
                    if (strtolower($relDef['type'] ?? '') === 'belongsto') {
                        $foreignKey = $relDef['foreign_key'] ?? Str::snake($relName).'_id';
                        if ($fieldName === $foreignKey) {
                            return false; // Exclude the foreign key field if the relationship is included
                        }
                    }
                }

                return true; // Include the field otherwise
            })
            ->map(fn ($fieldDef, $fieldName) => "            ExportColumn::make('{$fieldName}'),")
            ->implode("\n");

        // Add relationships to export columns
        if (! empty($relationships)) {
            $relationshipColumns = collect($relationships)
                ->map(function ($relDef, $relName) {
                    $type = strtolower($relDef['type'] ?? '');
                    if (in_array($type, ['belongsto', 'hasone'])) {
                        // For belongsTo/hasOne, export the related model's ID
                        return "            ExportColumn::make('{$relName}.id'), // Related {$relDef['model']} ID";
                    } elseif (in_array($type, ['belongstomany', 'hasmany'])) {
                        // For belongsToMany/hasMany, add a comment or a placeholder
                        // Exporting related collections directly as columns is not standard.
                        // You might need custom logic here, e.g., counting related items or listing IDs.
                        // return "            // ExportColumn::make('{$relName}'), // TODO: Implement custom export for {$type} relationship";
                        // Or a basic representation like count:
                        // return "            ExportColumn::make('{$relName}_count')->counts('{$relName}'),";
                        // Or related IDs (requires relationship to be loaded):
                        return "            ExportColumn::make('{$relName}')->formatStateUsing(fn (\$state) => \$state->pluck('id')->join(', ')), // Related {$relDef['model']} IDs";
                    }

                    return null; // Ignore other relationship types for now
                })
                ->filter() // Remove null entries
                ->implode("\n");

            if (! empty($relationshipColumns)) {
                $exportColumns .= "\n".$relationshipColumns;
            }
        }

        return <<<PHP
<?php

namespace Littleboy130491\Sumimasen\Filament\Exports;

use {$modelClass};
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class {$className} extends Exporter
{
    protected static ?string \$model = {$modelClass}::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
{$exportColumns}
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export \$export): string
    {
        \$body = 'Your {$modelName} export has completed and ' . number_format(\$export->successful_rows) . ' ' . Str::plural('row', \$export->successful_rows) . ' exported.';

        if (\$failedRowsCount = \$export->getFailedRowsCount()) {
            \$body .= ' ' . number_format(\$failedRowsCount) . ' ' . Str::plural('row', \$failedRowsCount) . ' failed to export.';
        }

        return \$body;
    }
}
PHP;
    }
}
