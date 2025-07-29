<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Exception;

/**
 * ShortPixel Image Optimization Command
 * 
 * This command integrates with ShortPixel API to optimize images in your Laravel application.
 * It automatically scans for images and compresses them while maintaining quality.
 * 
 * Basic Usage:
 *   php artisan cms:shortpixel-optimize --apiKey=YOUR_API_KEY
 * 
 * Advanced Usage:
 *   php artisan cms:shortpixel-optimize --apiKey=YOUR_KEY --folder=uploads --createWebP -v
 * 
 * Configuration:
 *   Set SHORTPIXEL_API_KEY in your .env file to avoid passing --apiKey each time
 * 
 * @see https://shortpixel.com/api-docs
 */
class ShortPixelOptimizeCommand extends Command
{
    protected $signature = 'cms:shortpixel-optimize
                            {--apiKey= : Your ShortPixel API Key}
                            {--folder= : The path of the folder to optimize (defaults to storage/app/public/media)}
                            {--compression=1 : The compression level (0=lossless, 1=lossy, 2=glossy)}
                            {--resize= : Resize images to [width]x[height] or [width]x[height]/[type]}
                            {--createWebP : Create additional WebP versions}
                            {--createAVIF : Create additional AVIF versions}
                            {--targetFolder= : Destination folder for optimized files}
                            {--webPath= : Map folder to web URL for download-based optimization}
                            {--backupBase= : Base directory for backups}
                            {--speed=10 : Set processing speed between 1-10}
                            {--exclude= : Comma separated list of subfolders to exclude}
                            {--recurseDepth= : How many subfolders deep to process}
                            {--clearLock : Clear existing folder lock}
                            {--retrySkipped : Retry all skipped items}';

    protected $description = 'Optimize images using ShortPixel API - compresses JPEG, PNG, GIF, WebP, and AVIF files';

    protected array $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
    protected string $lockFile = '';
    protected array $processedFiles = [];
    protected array $skippedFiles = [];
    protected int $totalSaved = 0;

    public function handle(): int
    {
        // Get API key from option or config
        $apiKey = $this->option('apiKey') ?: config('shortpixel.api_key');
        
        // Get folder path - defaults to 'media' which resolves to storage/app/public/media
        $folder = $this->option('folder') ?: config('shortpixel.folders.default_path', 'media');

        // Debug: Show API key source
        if ($this->getOutput()->isVerbose()) {
            $this->line("API Key source: " . ($this->option('apiKey') ? 'command option' : 'config file'));
            $this->line("API Key length: " . strlen($apiKey));
            $this->line("API Key preview: " . substr($apiKey, 0, 10) . '...');
        }

        // Validate API key
        if (!$apiKey) {
            $this->error('API Key is required. Provide via --apiKey option or set SHORTPIXEL_API_KEY in config.');
            $this->line('Get your API key from: https://shortpixel.com/');
            $this->line('Add to your .env file: SHORTPIXEL_API_KEY=your_api_key_here');
            return 1;
        }
        
        // Validate API key format (basic check)
        if (strlen($apiKey) < 10) {
            $this->error('Invalid API key format. Please check your API key.');
            return 1;
        }

        // Resolve and validate folder path
        $folderPath = $this->resolveFolderPath($folder);
        
        if (!File::exists($folderPath) || !File::isDirectory($folderPath)) {
            $this->error("Folder does not exist or is not a directory: {$folderPath}");
            $this->line("Tip: Use --folder=/absolute/path or relative path like 'uploads'");
            return 1;
        }

        // Set up locking mechanism to prevent concurrent runs
        $this->lockFile = $folderPath . '/.shortpixel.lock';

        if ($this->option('clearLock')) {
            $this->clearLock();
        }

        // Check for existing lock (prevents concurrent optimization)
        if ($this->checkLock()) {
            $this->error('Another ShortPixel process is already running on this folder. Use --clearLock to override.');
            return 1;
        }

        // Create lock file
        $this->createLock();

        try {
            $this->info("Starting ShortPixel optimization for: {$folderPath}");
            $this->line("ShortPixel will automatically skip already optimized files and only charge credits for improved images.");
            
            // Scan for image files
            $files = $this->scanFolder($folderPath);
            $this->info("Found " . count($files) . " image files to process.");
            
            if (count($files) === 0) {
                $this->warn("No image files found in the specified folder.");
                return 0;
            }

            $this->line("Starting optimization with ShortPixel API...");
            
            // Process the files using ShortPixel PHP package with proper folder optimization
            $this->optimizeWithFolderMethod($folderPath, $apiKey);

            // Show results summary
            $this->displaySummary();

        } catch (Exception $e) {
            $this->error("Error during optimization: " . $e->getMessage());
            return 1;
        } finally {
            // Always clean up lock file
            $this->clearLock();
        }

        return 0;
    }

    /**
     * Resolve folder path with intelligent fallbacks
     * 
     * Priority order:
     * 1. Absolute paths (used as-is)
     * 2. storage/app/public/{folder} (Laravel storage)
     * 3. public/{folder} (public directory)
     * 4. ./{folder} (current working directory)
     */
    protected function resolveFolderPath(string $folder): string
    {
        if ($this->isAbsolutePath($folder)) {
            return $folder;
        }

        // Try relative to storage/app/public first (most common for Laravel apps)
        $storagePath = storage_path('app/public/' . $folder);
        if (File::exists($storagePath)) {
            return $storagePath;
        }

        // Try relative to public folder
        $publicPath = public_path($folder);
        if (File::exists($publicPath)) {
            return $publicPath;
        }

        // Default to current working directory
        return getcwd() . '/' . $folder;
    }

    /**
     * Scan folder for supported image files
     * 
     * Supports: JPEG, PNG, GIF, WebP, AVIF
     * Respects --exclude and --recurseDepth options
     */
    protected function scanFolder(string $folderPath): array
    {
        $files = [];
        $excludeFolders = $this->option('exclude') ? explode(',', $this->option('exclude')) : [];
        $recurseDepth = $this->option('recurseDepth');
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        if ($recurseDepth !== null) {
            $iterator->setMaxDepth((int) $recurseDepth);
        }

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($folderPath . '/', '', $file->getPathname());
                
                // Check if file is in excluded folder
                $skip = false;
                foreach ($excludeFolders as $excludeFolder) {
                    if (str_starts_with($relativePath, trim($excludeFolder))) {
                        $skip = true;
                        break;
                    }
                }

                if ($skip) {
                    continue;
                }

                $extension = strtolower($file->getExtension());
                if (in_array($extension, $this->supportedFormats)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    protected function optimizeFilesWithPackage(array $files, string $apiKey): void
    {
        // Check if ShortPixel package is available
        if (!class_exists('\ShortPixel\ShortPixel')) {
            $this->error('ShortPixel PHP package not found. Install it with: composer require shortpixel/shortpixel-php');
            return;
        }

        // Initialize ShortPixel
        \ShortPixel\ShortPixel::setKey($apiKey);
        
        // Build options with persistence enabled for duplicate detection
        $options = [
            'lossy' => (int) $this->option('compression'),
            'wait' => 300, // Wait up to 5 minutes for processing
            'persist_type' => 'text', // Enable .shortpixel file tracking
            'persist_name' => '.shortpixel', // Track optimization status
        ];
        
        // Add backup configuration if specified
        $backupBase = $this->option('backupBase');
        if ($backupBase) {
            $options['backup_path'] = $backupBase;
        }
        
        // Add resize options if specified
        if ($this->option('resize')) {
            $resize = $this->option('resize');
            if (strpos($resize, 'x') !== false) {
                $parts = explode('x', $resize);
                $options['resize'] = 1; // Enable resize
                $options['resize_width'] = (int) $parts[0];
                if (isset($parts[1])) {
                    $resizeParts = explode('/', $parts[1]);
                    $options['resize_height'] = (int) $resizeParts[0];
                    if (isset($resizeParts[1])) {
                        $options['resize'] = (int) $resizeParts[1]; // resize type
                    }
                }
            }
        }
        
        // Add format conversion options
        $convertOptions = $this->getConvertOptions();
        if (!empty($convertOptions)) {
            $options['convertto'] = $convertOptions;
        }
        
        \ShortPixel\ShortPixel::setOptions($options);

        // Create progress bar
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $progressBar->setMessage('Starting optimization...');
        $progressBar->start();

        $speed = (int) $this->option('speed');
        $chunks = array_chunk($files, $speed);

        foreach ($chunks as $chunkIndex => $chunk) {
            $progressBar->setMessage("Processing chunk " . ($chunkIndex + 1) . " of " . count($chunks));
            $this->processChunkWithPackage($chunk, $progressBar);
        }

        $progressBar->setMessage('Optimization complete!');
        $progressBar->finish();
        $this->line(''); // New line after progress bar
    }

    protected function optimizeWithFolderMethod(string $folderPath, string $apiKey): void
    {
        // Check if ShortPixel package is available
        if (!class_exists('\\ShortPixel\\ShortPixel')) {
            $this->error('ShortPixel PHP package not found. Install it with: composer require shortpixel/shortpixel-php');
            return;
        }

        // Initialize ShortPixel
        \ShortPixel\ShortPixel::setKey($apiKey);
        
        // Build options with persistence enabled for duplicate detection
        $options = [
            'lossy' => (int) $this->option('compression'),
            'wait' => 300, // Wait up to 5 minutes for processing
            'persist_type' => 'text', // Enable .shortpixel file tracking for duplicate detection
            'persist_name' => '.shortpixel', // Track optimization status in .shortpixel files
        ];
        
        // Get backup path from option or config
        $backupBase = $this->option('backupBase');
        
        // If no option provided, check config
        if (!$backupBase && config('shortpixel.backup.enabled', true)) {
            $backupBase = config('shortpixel.backup.base_path', storage_path('app/shortpixel-backups'));
        }
        
        if ($backupBase) {
            // Ensure backup directory exists
            File::ensureDirectoryExists($backupBase);
            if ($this->getOutput()->isVerbose()) {
                $this->line("Backup enabled: Original files will be saved to {$backupBase}");
            }
        }
        
        // Add resize options if specified
        if ($this->option('resize')) {
            $resize = $this->option('resize');
            if (strpos($resize, 'x') !== false) {
                $parts = explode('x', $resize);
                $options['resize'] = 1; // Enable resize
                $options['resize_width'] = (int) $parts[0];
                if (isset($parts[1])) {
                    $resizeParts = explode('/', $parts[1]);
                    $options['resize_height'] = (int) $resizeParts[0];
                    if (isset($resizeParts[1])) {
                        $options['resize'] = (int) $resizeParts[1]; // resize type
                    }
                }
            }
        }
        
        // Add format conversion options
        $convertOptions = $this->getConvertOptions();
        if (!empty($convertOptions)) {
            $options['convertto'] = $convertOptions;
        }
        
        // Set target folder if specified
        $targetFolder = $this->option('targetFolder');
        if ($targetFolder) {
            File::ensureDirectoryExists($targetFolder);
            $options['base_path'] = $targetFolder;
        }
        
        \ShortPixel\ShortPixel::setOptions($options);

        // Get exclude patterns
        $excludeFolders = $this->option('exclude') ? explode(',', $this->option('exclude')) : [];
        $recurseDepth = $this->option('recurseDepth') ? (int) $this->option('recurseDepth') : PHP_INT_MAX;
        
        // Get folder info to show progress and check what needs processing
        $folderInfo = null;
        try {
            $folderInfo = \ShortPixel\folderInfo($folderPath, true, true, $excludeFolders, false, $recurseDepth);
            
            if ($folderInfo && isset($folderInfo->total)) {
                $this->info("Folder analysis: {$folderInfo->total} images total");
                if (isset($folderInfo->succeeded)) {
                    $this->info("Already optimized: {$folderInfo->succeeded} images");
                    // Count already optimized files as skipped
                    for ($i = 0; $i < $folderInfo->succeeded; $i++) {
                        $this->skippedFiles[] = "already-optimized-file-{$i}";
                    }
                }
                if (isset($folderInfo->pending)) {
                    $this->info("Pending optimization: {$folderInfo->pending} images");
                }
                if (isset($folderInfo->failed)) {
                    $this->info("Failed images: {$folderInfo->failed} images");
                    // Count failed files as skipped
                    for ($i = 0; $i < $folderInfo->failed; $i++) {
                        $this->skippedFiles[] = "failed-file-{$i}";
                    }
                }
                if (isset($folderInfo->same)) {
                    $this->info("Same (no optimization needed): {$folderInfo->same} images");
                    // Count "same" files as skipped
                    for ($i = 0; $i < $folderInfo->same; $i++) {
                        $this->skippedFiles[] = "same-file-{$i}";
                    }
                }
                
                // Show unprocessed files count
                $unprocessed = $folderInfo->total - ($folderInfo->succeeded ?? 0) - ($folderInfo->same ?? 0) - ($folderInfo->failed ?? 0);
                if ($this->getOutput()->isVerbose()) {
                    $this->info("Unprocessed files: {$unprocessed}");
                }
            }
        } catch (Exception $e) {
            $this->warn("Could not analyze folder: " . $e->getMessage());
        }
        
        // Process folder in chunks using ShortPixel's fromFolder method
        $speed = (int) $this->option('speed');
        $processed = 0;
        $totalProcessed = 0;
        
        do {
            if ($this->getOutput()->isVerbose()) {
                $this->line("Processing batch of up to {$speed} files...");
            }
            
            try {
                // Use fromFolder to get a batch of files that need processing
                // Third parameter of toFiles() is the backup path
                $result = \ShortPixel\fromFolder($folderPath, $speed, $excludeFolders, false, \ShortPixel\ShortPixel::CLIENT_MAX_BODY_SIZE, $recurseDepth)
                    ->wait(300)
                    ->toFiles($targetFolder ?: $folderPath, null, $backupBase);
                
                // Process results
                $processed = $this->processFolderResult($result);
                $totalProcessed += $processed;
                
                if ($processed > 0) {
                    $this->info("✓ Processed {$processed} files in this batch");
                }
                
            } catch (\ShortPixel\ClientException $e) {
                if ($e->getCode() === 2) {
                    // No more files to process
                    if ($this->getOutput()->isVerbose()) {
                        $this->info("✓ All files have been processed or no files need optimization");
                    }
                    break;
                } else {
                    $this->error("ShortPixel error: " . $e->getMessage());
                    break;
                }
            } catch (Exception $e) {
                $this->error("Error processing folder: " . $e->getMessage());
                break;
            }
            
        } while ($processed > 0);
        
        if ($totalProcessed > 0) {
            $this->info("Total files processed in this session: {$totalProcessed}");
        }
    }

    protected function processFolderResult($result): int
    {
        $processed = 0;
        
        // Handle successful optimizations
        if (isset($result->succeeded) && count($result->succeeded) > 0) {
            foreach ($result->succeeded as $data) {
                $this->processedFiles[] = $data->OriginalURL ?? 'unknown';
                $processed++;
                
                // Calculate savings if data is available
                if (isset($data->OriginalSize) && isset($data->LossySize)) {
                    $savedBytes = $data->OriginalSize - $data->LossySize;
                    $this->totalSaved += $savedBytes;
                    
                    if ($this->getOutput()->isVerbose()) {
                        $percentSaved = round(($savedBytes / $data->OriginalSize) * 100, 2);
                        $fileName = basename($data->OriginalURL ?? 'unknown');
                        $this->info("✓ Optimized: {$fileName} (saved {$percentSaved}% / " . $this->formatBytes($savedBytes) . ")");
                    }
                }
            }
        }
        
        // Handle files that were already optimized (same)
        if (isset($result->same) && count($result->same) > 0) {
            foreach ($result->same as $data) {
                $this->processedFiles[] = $data->OriginalURL ?? 'unknown';
                $processed++;
                
                if ($this->getOutput()->isVerbose()) {
                    $fileName = basename($data->OriginalURL ?? 'unknown');
                    $this->info("✓ Already optimized: {$fileName} (no further optimization possible)");
                }
            }
        }
        
        // Handle pending files
        if (isset($result->pending) && count($result->pending) > 0) {
            foreach ($result->pending as $data) {
                $this->skippedFiles[] = $data->OriginalURL ?? 'unknown';
                
                if (count($this->skippedFiles) <= 3 || $this->getOutput()->isVerbose()) {
                    $fileName = basename($data->OriginalURL ?? 'unknown');
                    $this->warn("Skipped {$fileName}: Processing timeout (still pending)");
                }
            }
        }
        
        // Handle failed files
        if (isset($result->failed) && count($result->failed) > 0) {
            foreach ($result->failed as $data) {
                $this->skippedFiles[] = $data->OriginalURL ?? 'unknown';
                $errorMessage = $data->Message ?? 'Processing failed';
                
                if (count($this->skippedFiles) <= 3 || $this->getOutput()->isVerbose()) {
                    $fileName = basename($data->OriginalURL ?? 'unknown');
                    $this->warn("Skipped {$fileName}: {$errorMessage}");
                }
            }
        }
        
        return $processed;
    }

    protected function processChunkWithPackage(array $files, $progressBar = null): void
    {
        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            
            if ($progressBar) {
                $progressBar->setMessage("Processing: {$fileName}");
            }
            
            try {
                // Create backup if specified
                $backupBase = $this->option('backupBase');
                if ($backupBase) {
                    $this->createBackup($filePath, $backupBase);
                }
                
                // Determine target folder
                $targetFolder = $this->option('targetFolder');
                $outputPath = $targetFolder ?: dirname($filePath);
                
                // Use ShortPixel package for optimization
                $result = \ShortPixel\fromFile($filePath)->wait(300)->toFiles($outputPath);
                
                // Handle ShortPixel response structure
                if (isset($result->succeeded) && count($result->succeeded) > 0) {
                    $this->processedFiles[] = $filePath;
                    $data = $result->succeeded[0];
                    
                    // Calculate savings if data is available
                    if (isset($data->OriginalSize) && isset($data->LossySize)) {
                        $savedBytes = $data->OriginalSize - $data->LossySize;
                        $this->totalSaved += $savedBytes;
                        
                        if ($this->getOutput()->isVerbose()) {
                            $percentSaved = round(($savedBytes / $data->OriginalSize) * 100, 2);
                            $this->info("✓ Optimized: {$fileName} (saved {$percentSaved}% / " . $this->formatBytes($savedBytes) . ")");
                        }
                    }
                    
                    // Handle additional format creation
                    if ($this->option('createWebP') || $this->option('createAVIF')) {
                        $this->createAdditionalFormats($filePath, $outputPath);
                    }
                    
                } elseif (isset($result->same) && count($result->same) > 0) {
                    $this->processedFiles[] = $filePath;
                    if ($this->getOutput()->isVerbose()) {
                        $this->info("✓ Already optimized: {$fileName} (no further optimization possible)");
                    }
                    
                } elseif (isset($result->pending) && count($result->pending) > 0) {
                    $this->skippedFiles[] = $filePath;
                    if (count($this->skippedFiles) <= 3 || $this->getOutput()->isVerbose()) {
                        $this->warn("Skipped {$fileName}: Processing timeout (still pending)");
                    }
                    
                } else {
                    $this->skippedFiles[] = $filePath;
                    $errorMessage = 'Unknown error or processing failed';
                    
                    // Try to get error details from failed array
                    if (isset($result->failed) && count($result->failed) > 0) {
                        $errorData = $result->failed[0];
                        $errorMessage = isset($errorData->Message) ? $errorData->Message : 'Processing failed';
                    }
                    
                    if (count($this->skippedFiles) <= 3 || $this->getOutput()->isVerbose()) {
                        $this->warn("Skipped {$fileName}: {$errorMessage}");
                    }
                }
                
                if ($progressBar) {
                    $progressBar->advance();
                }
                
            } catch (Exception $e) {
                $this->skippedFiles[] = $filePath;
                
                if ($progressBar) {
                    $progressBar->setMessage("Skipped: {$fileName} - " . $e->getMessage());
                    $progressBar->advance();
                }
                
                if (count($this->skippedFiles) <= 3 || $this->getOutput()->isVerbose()) {
                    $this->warn("Skipped {$fileName}: " . $e->getMessage());
                }
            }
        }
    }

    protected function getConvertOptions(): string
    {
        $options = [];
        
        if ($this->option('createWebP')) {
            $options[] = '+webp';
        }
        
        if ($this->option('createAVIF')) {
            $options[] = '+avif';
        }

        return implode('|', $options);
    }

    protected function createBackup(string $filePath, string $backupBase): void
    {
        $relativePath = str_replace(getcwd(), '', $filePath);
        $backupPath = rtrim($backupBase, '/') . '/' . ltrim($relativePath, '/');
        
        File::ensureDirectoryExists(dirname($backupPath));
        File::copy($filePath, $backupPath);
        
        if ($this->getOutput()->isVerbose()) {
            $this->line("  📁 Backed up to: " . $backupPath);
        }
    }

    protected function createAdditionalFormats(string $originalPath, string $outputPath): void
    {
        // Note: WebP/AVIF creation is handled via convertto option in main optimization
        // The ShortPixel package doesn't have separate generateWebP/generateAVIF methods
        // These formats should be specified in the main optimize call via convertto option
        
        if ($this->getOutput()->isVerbose()) {
            $this->line("  ℹ️ Additional formats (WebP/AVIF) are handled via convertto option during optimization");
        }
    }

    protected function checkLock(): bool
    {
        if (!File::exists($this->lockFile)) {
            return false;
        }

        $lockTime = File::lastModified($this->lockFile);
        $now = time();

        // Lock expires after 6 minutes
        if (($now - $lockTime) > 360) {
            $this->clearLock();
            return false;
        }

        return true;
    }

    protected function createLock(): void
    {
        File::put($this->lockFile, time());
    }

    protected function clearLock(): void
    {
        if (File::exists($this->lockFile)) {
            File::delete($this->lockFile);
        }
    }

    /**
     * Display optimization results summary
     */
    protected function displaySummary(): void
    {
        $processedCount = count($this->processedFiles);
        $skippedCount = count($this->skippedFiles);

        $this->info("\n" . str_repeat('=', 50));
        $this->info("SHORTPIXEL OPTIMIZATION SUMMARY");
        $this->info(str_repeat('=', 50));
        $this->info("Files processed: {$processedCount}");
        $this->info("Files skipped: {$skippedCount}");
        $this->info("Total bytes saved: " . $this->formatBytes($this->totalSaved));
        
        if ($processedCount > 0) {
            $this->line("\n✅ Optimization complete! ShortPixel creates .shortpixel files to track optimization status.");
        }
        
        if ($skippedCount > 0 && $this->getOutput()->isVerbose()) {
            $this->warn("\nSkipped files:");
            foreach ($this->skippedFiles as $file) {
                $this->line("  - " . basename($file));
            }
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Check if a path is absolute
     */
    protected function isAbsolutePath(string $path): bool
    {
        // Unix/Linux/Mac absolute paths start with /
        if (str_starts_with($path, '/')) {
            return true;
        }
        
        // Windows absolute paths (C:\, D:\, etc.)
        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return true;
        }
        
        return false;
    }



}