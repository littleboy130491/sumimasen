<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class CreateImporterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:importer:from-yaml
                            {yaml_file? : The path to the YAML definition file (defaults to schemas/models.yaml)}
                            {--model= : Specify a single model from the YAML to generate the importer for}
                            {--force : Overwrite existing importer files without asking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Filament Importer class(es) from a YAML definition file';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
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

        if (!$this->files->exists($yamlFilePath)) {
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

        if (!isset($schema['models']) || !is_array($schema['models'])) {
            $this->error("Invalid YAML structure. Missing 'models' key or it's not an array.");
            return 1;
        }

        $modelsToProcess = $schema['models'];

        // Filter for a specific model if the option is provided
        if ($specificModel) {
            if (!isset($modelsToProcess[$specificModel])) {
                $this->error("Model '{$specificModel}' not found in the YAML file.");
                return 1;
            }
            $modelsToProcess = [$specificModel => $modelsToProcess[$specificModel]];
        }

        $generatedCount = 0;
        foreach ($modelsToProcess as $modelName => $modelDefinition) {
            $this->info("Processing model for importer: {$modelName}");
            if ($this->generateImporterFile($modelName, $modelDefinition, $force)) {
                $generatedCount++;
            }
        }

        $this->info('Importer generation process completed.');
        return 0;
    }

    /**
     * Generate the Filament Importer file.
     *
     * @param string $modelName
     * @param array $definition
     * @param bool $force
     * @return bool Returns true if the file was generated, false otherwise.
     */
    protected function generateImporterFile(string $modelName, array $definition, bool $force): bool
    {
        $className = Str::studly($modelName) . 'Importer';
        $filePath = app_path("Filament/Imports/{$className}.php"); // Assumes app/Filament/Imports path

        // Check if file exists and prompt for overwrite unless --force is used
        if ($this->files->exists($filePath) && !$force) {
            if (!$this->confirm("Importer file [{$filePath}] already exists. Overwrite?", false)) {
                $this->line("Skipping generation for importer: {$className}");
                return false;
            }
        }

        // Ensure the directory exists
        $directoryPath = dirname($filePath);
        if (!$this->files->isDirectory($directoryPath)) {
            $this->files->makeDirectory($directoryPath, 0755, true);
        }

        // Build the importer content
        $content = $this->buildImporterContent($modelName, $className, $definition);

        // Write the file
        if ($this->files->put($filePath, $content) !== false) {
            $this->line("<info>Created Importer:</info> {$filePath}");
            return true;
        } else {
            $this->error("Failed to write importer file: {$filePath}");
            return false;
        }
    }

    /**
     * Build the full content of the Importer file.
     *
     * @param string $modelName
     * @param string $className
     * @param array $definition
     * @return string
     */
    protected function buildImporterContent(string $modelName, string $className, array $definition): string
    {
        $modelClass = 'Littleboy130491\Sumimasen\\Models\\' . Str::studly($modelName);
        $modelVariableName = Str::camel($modelName);
        $fields = $definition['fields'] ?? [];

        $importColumns = collect($fields)
            ->map(fn($fieldDef, $fieldName) => "            ImportColumn::make('{$fieldName}'),")
            ->implode("\n");

        // Add relationships to import columns if needed (basic example)
        if (!empty($definition['relationships'])) {
            $relationshipColumns = collect($definition['relationships'])
                ->filter(fn($relDef) => in_array(strtolower($relDef['type'] ?? ''), ['belongsto', 'hasone'])) // Only include simple relationships for now
                ->map(fn($relDef, $relName) => "            ImportColumn::make('{$relName}_id'), // Example: Import related model ID") // Basic example
                ->implode("\n");
            if (!empty($relationshipColumns)) {
                $importColumns .= "\n" . $relationshipColumns;
            }
        }


        return <<<PHP
<?php

namespace Littleboy130491\Sumimasen\Filament\Imports;

use {$modelClass};
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class {$className} extends Importer
{
    protected static ?string \$model = {$modelClass}::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->ignoreDuplicates()
                ->requiredMapping()
                ->numeric()
                ->rules(['nullable']),
{$importColumns}
        ];
    }

    public {$modelClass} \$record;

    public function resolveRecord(): ?{$modelClass}
    {
        // return {$modelClass}::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => \$this->data['email'],
        // ]);

        return new {$modelClass}();
    }

    public static function getCompletedNotificationBody(Import \$import): string
    {
        \$body = 'Your {$modelName} import has completed and ' . number_format(\$import->successful_rows) . ' ' . Str::plural('row', \$import->successful_rows) . ' imported.';

        if (\$failedRowsCount = \$import->getFailedRowsCount()) {
            \$body .= ' ' . number_format(\$failedRowsCount) . ' ' . Str::plural('row', \$failedRowsCount) . ' failed to import.';
        }

        return \$body;
    }
}
PHP;
    }
}