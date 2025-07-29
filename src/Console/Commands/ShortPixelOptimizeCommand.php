<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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

        // Validate API key
        if (!$apiKey) {
            $this->error('API Key is required. Provide via --apiKey option or set SHORTPIXEL_API_KEY in config.');
            $this->line('Get your API key from: https://shortpixel.com/');
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

            // Process the files
            $this->optimizeFiles($files, $apiKey);

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
        if (File::isAbsolute($folder)) {
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

    protected function optimizeFiles(array $files, string $apiKey): void
    {
        $webPath = $this->option('webPath');
        $speed = (int) $this->option('speed');
        $chunks = array_chunk($files, $speed);

        foreach ($chunks as $chunk) {
            $this->processChunk($chunk, $apiKey, $webPath);
        }
    }

    protected function processChunk(array $files, string $apiKey, ?string $webPath): void
    {
        foreach ($files as $filePath) {
            try {
                if ($webPath) {
                    $this->optimizeViaUrl($filePath, $apiKey, $webPath);
                } else {
                    $this->optimizeViaUpload($filePath, $apiKey);
                }
            } catch (Exception $e) {
                $this->skippedFiles[] = $filePath;
                if ($this->getOutput()->isVerbose()) {
                    $this->warn("Skipped {$filePath}: " . $e->getMessage());
                }
            }
        }
    }

    protected function optimizeViaUpload(string $filePath, string $apiKey): void
    {
        $fileSize = File::size($filePath);
        $fileName = basename($filePath);

        if ($this->getOutput()->isVerbose()) {
            $this->line("Processing: {$fileName} (" . $this->formatBytes($fileSize) . ")");
        }

        $response = Http::attach(
            'file1', File::get($filePath), $fileName
        )->post('https://api.shortpixel.com/v2/reducer.php', [
            'key' => $apiKey,
            'plugin_version' => '1.0.0',
            'lossy' => $this->option('compression'),
            'resize' => $this->option('resize') ?: '',
            'convertto' => $this->getConvertOptions(),
        ]);

        $this->handleApiResponse($response, $filePath, $fileSize);
    }

    protected function optimizeViaUrl(string $filePath, string $apiKey, string $webPath): void
    {
        $relativePath = str_replace(storage_path('app/public/'), '', $filePath);
        $fileUrl = rtrim($webPath, '/') . '/' . $relativePath;
        $fileName = basename($filePath);

        if ($this->getOutput()->isVerbose()) {
            $this->line("Processing via URL: {$fileName}");
        }

        $response = Http::post('https://api.shortpixel.com/v2/reducer.php', [
            'key' => $apiKey,
            'plugin_version' => '1.0.0',
            'urllist' => [$fileUrl],
            'lossy' => $this->option('compression'),
            'resize' => $this->option('resize') ?: '',
            'convertto' => $this->getConvertOptions(),
        ]);

        $this->handleApiResponse($response, $filePath, File::size($filePath));
    }

    protected function handleApiResponse($response, string $filePath, int $originalSize): void
    {
        if (!$response->successful()) {
            throw new Exception("API request failed with status: " . $response->status());
        }

        $data = $response->json();

        if (isset($data[0]['Status']['Code']) && $data[0]['Status']['Code'] == 1) {
            $optimizedUrl = $data[0]['OptimizedURL'];
            $savedBytes = $data[0]['OriginalSize'] - $data[0]['OptimizedSize'];
            $percentSaved = round(($savedBytes / $data[0]['OriginalSize']) * 100, 2);

            $this->downloadOptimizedFile($optimizedUrl, $filePath);
            $this->totalSaved += $savedBytes;
            $this->processedFiles[] = $filePath;

            if ($this->getOutput()->isVerbose()) {
                $this->info("✓ Optimized: " . basename($filePath) . " (saved {$percentSaved}% / " . $this->formatBytes($savedBytes) . ")");
            }

            // Handle WebP/AVIF creation
            if ($this->option('createWebP') && isset($data[0]['WebPURL'])) {
                $this->downloadAdditionalFormat($data[0]['WebPURL'], $filePath, 'webp');
            }

            if ($this->option('createAVIF') && isset($data[0]['AVIFURL'])) {
                $this->downloadAdditionalFormat($data[0]['AVIFURL'], $filePath, 'avif');
            }

        } else {
            $message = $data[0]['Status']['Message'] ?? 'Unknown error';
            throw new Exception($message);
        }
    }

    protected function downloadOptimizedFile(string $url, string $filePath): void
    {
        $targetFolder = $this->option('targetFolder');
        $backupBase = $this->option('backupBase');

        // Create backup if specified
        if ($backupBase) {
            $this->createBackup($filePath, $backupBase);
        }

        // Determine final file path
        if ($targetFolder) {
            $relativePath = str_replace(dirname($filePath), '', $filePath);
            $finalPath = rtrim($targetFolder, '/') . '/' . ltrim($relativePath, '/');
            File::ensureDirectoryExists(dirname($finalPath));
        } else {
            $finalPath = $filePath;
        }

        // Download optimized file
        $optimizedContent = Http::get($url)->body();
        File::put($finalPath, $optimizedContent);
    }

    protected function downloadAdditionalFormat(string $url, string $originalPath, string $format): void
    {
        $pathInfo = pathinfo($originalPath);
        $newPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.' . $format;
        
        $content = Http::get($url)->body();
        File::put($newPath, $content);

        if ($this->getOutput()->isVerbose()) {
            $this->line("  ✓ Created {$format}: " . basename($newPath));
        }
    }

    protected function createBackup(string $filePath, string $backupBase): void
    {
        $relativePath = str_replace(getcwd(), '', $filePath);
        $backupPath = rtrim($backupBase, '/') . '/' . ltrim($relativePath, '/');
        
        File::ensureDirectoryExists(dirname($backupPath));
        File::copy($filePath, $backupPath);
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

}