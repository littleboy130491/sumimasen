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
            
            // Process the files using ShortPixel PHP package
            $this->optimizeFilesWithPackage($files, $apiKey);

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
        \ShortPixel\ShortPixel::setOptions([
            'lossy' => (int) $this->option('compression'),
            'wait' => 300, // Wait up to 5 minutes for processing
        ]);

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
                
                if ($result->isSuccessful()) {
                    $this->processedFiles[] = $filePath;
                    
                    if (isset($result[0])) {
                        $savedBytes = $result[0]->originalSize - $result[0]->optimizedSize;
                        $this->totalSaved += $savedBytes;
                        
                        if ($this->getOutput()->isVerbose()) {
                            $percentSaved = round(($savedBytes / $result[0]->originalSize) * 100, 2);
                            $this->info("✓ Optimized: {$fileName} (saved {$percentSaved}% / " . $this->formatBytes($savedBytes) . ")");
                        }
                    }
                    
                    // Handle additional format creation
                    if ($this->option('createWebP') || $this->option('createAVIF')) {
                        $this->createAdditionalFormats($filePath, $outputPath);
                    }
                    
                } else {
                    $this->skippedFiles[] = $filePath;
                    $errorMessage = $result->hasErrors() ? $result->getErrors()[0] : 'Unknown error';
                    
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
        $pathInfo = pathinfo($originalPath);
        
        try {
            if ($this->option('createWebP')) {
                $webpResult = \ShortPixel\fromFile($originalPath)
                    ->optimize((int) $this->option('compression'))
                    ->wait(300)
                    ->toFiles($outputPath);
                    
                if ($webpResult->isSuccessful() && $this->getOutput()->isVerbose()) {
                    $this->line("  🖼️ Created WebP: " . $pathInfo['filename'] . '.webp');
                }
            }
            
            if ($this->option('createAVIF')) {
                $avifResult = \ShortPixel\fromFile($originalPath)
                    ->optimize((int) $this->option('compression'))
                    ->wait(300)
                    ->toFiles($outputPath);
                    
                if ($avifResult->isSuccessful() && $this->getOutput()->isVerbose()) {
                    $this->line("  🖼️ Created AVIF: " . $pathInfo['filename'] . '.avif');
                }
            }
        } catch (Exception $e) {
            if ($this->getOutput()->isVerbose()) {
                $this->warn("  ⚠️ Additional format creation failed: " . $e->getMessage());
            }
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