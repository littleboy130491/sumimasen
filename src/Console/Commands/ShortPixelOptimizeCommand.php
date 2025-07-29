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

            $this->line("Connecting to ShortPixel API...");
            
            // Test API key validity
            if (!$this->testApiConnection($apiKey)) {
                return 1;
            }
            
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

    protected function optimizeFiles(array $files, string $apiKey): void
    {
        $webPath = $this->option('webPath');
        $speed = (int) $this->option('speed');
        $chunks = array_chunk($files, $speed);

        // Create progress bar
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $progressBar->setMessage('Starting optimization...');
        $progressBar->start();

        foreach ($chunks as $chunkIndex => $chunk) {
            $progressBar->setMessage("Processing chunk " . ($chunkIndex + 1) . " of " . count($chunks));
            $this->processChunk($chunk, $apiKey, $webPath, $progressBar);
        }

        $progressBar->setMessage('Optimization complete!');
        $progressBar->finish();
        $this->line(''); // New line after progress bar
    }

    protected function processChunk(array $files, string $apiKey, ?string $webPath, $progressBar = null): void
    {
        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            
            if ($progressBar) {
                $progressBar->setMessage("Processing: {$fileName}");
            }
            
            try {
                if ($webPath) {
                    $this->optimizeViaUrl($filePath, $apiKey, $webPath);
                } else {
                    $this->optimizeViaUpload($filePath, $apiKey);
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
                
                // Always show error for the first few files to help debug
                if (count($this->skippedFiles) <= 3 || $this->getOutput()->isVerbose()) {
                    $this->warn("Skipped {$fileName}: " . $e->getMessage());
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

        // Build clean request data
        $requestData = [
            'key' => $apiKey,
            'plugin_version' => '1.0.0',
            'lossy' => (int) $this->option('compression'),
        ];
        
        // Add optional parameters only if they have values
        if ($this->option('resize')) {
            $requestData['resize'] = $this->option('resize');
        }
        
        $convertOptions = $this->getConvertOptions();
        if (!empty($convertOptions)) {
            $requestData['convertto'] = $convertOptions;
        }

        $response = Http::timeout(120)->attach(
            'file1', File::get($filePath), $fileName
        )->post('https://api.shortpixel.com/v2/reducer.php', $requestData);

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
            $errorBody = $response->body();
            throw new Exception("API request failed with status: " . $response->status() . ". Response: " . $errorBody);
        }

        $data = $response->json();
        
        // Handle different response formats
        $responseData = $data;
        if (isset($data[0])) {
            // Array format (for urllist requests)
            $responseData = $data[0];
        } elseif (isset($data['Status'])) {
            // Direct object format (for file upload requests)
            $responseData = $data;
        } else {
            throw new Exception("Invalid API response format. Response: " . json_encode($data));
        }

        if (isset($responseData['Status']['Code']) && $responseData['Status']['Code'] == 1) {
            $optimizedUrl = $responseData['OptimizedURL'];
            $savedBytes = $responseData['OriginalSize'] - $responseData['OptimizedSize'];
            $percentSaved = round(($savedBytes / $responseData['OriginalSize']) * 100, 2);

            $this->downloadOptimizedFile($optimizedUrl, $filePath);
            $this->totalSaved += $savedBytes;
            $this->processedFiles[] = $filePath;

            if ($this->getOutput()->isVerbose()) {
                $this->info("✓ Optimized: " . basename($filePath) . " (saved {$percentSaved}% / " . $this->formatBytes($savedBytes) . ")");
            }

            // Handle WebP/AVIF creation
            if ($this->option('createWebP') && isset($responseData['WebPURL'])) {
                $this->downloadAdditionalFormat($responseData['WebPURL'], $filePath, 'webp');
            }

            if ($this->option('createAVIF') && isset($responseData['AVIFURL'])) {
                $this->downloadAdditionalFormat($responseData['AVIFURL'], $filePath, 'avif');
            }

        } else {
            $statusCode = $responseData['Status']['Code'] ?? 'Unknown';
            $message = $responseData['Status']['Message'] ?? 'Unknown error';
            throw new Exception("ShortPixel Error (Code: {$statusCode}): {$message}");
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

    /**
     * Test API connection with a simple request
     */
    protected function testApiConnection(string $apiKey): bool
    {
        try {
            $response = Http::timeout(10)->post('https://api.shortpixel.com/v2/reducer.php', [
                'key' => $apiKey,
                'plugin_version' => '1.0.0',
                'urllist' => ['https://via.placeholder.com/1x1.png'], // Tiny test image
                'lossy' => 1,
            ]);

            if (!$response->successful()) {
                $this->error("Failed to connect to ShortPixel API (Status: " . $response->status() . ")");
                return false;
            }

            $data = $response->json();
            
            if (isset($data[0]['Status']['Code'])) {
                $code = $data[0]['Status']['Code'];
                $message = $data[0]['Status']['Message'] ?? '';
                
                if ($code == -3) {
                    $this->error("Invalid API key. Please check your SHORTPIXEL_API_KEY.");
                    return false;
                } elseif ($code == -4) {
                    $this->error("No credits left on your ShortPixel account.");
                    return false;
                } elseif ($code < 0) {
                    $this->error("ShortPixel API Error (Code: {$code}): {$message}");
                    return false;
                }
                
                $this->info("✓ API connection successful!");
                return true;
            }
            
            $this->error("Unexpected API response format.");
            return false;
            
        } catch (Exception $e) {
            $this->error("Failed to connect to ShortPixel API: " . $e->getMessage());
            return false;
        }
    }

}