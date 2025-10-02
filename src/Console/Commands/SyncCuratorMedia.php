<?php

namespace Littleboy130491\Sumimasen\Console\Commands;

use Awcodes\Curator\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncCuratorMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:sync
                            {--disk=public   : Which filesystem disk to scan}
                            {--dir=media     : Directory within that disk}
                            {--update       : Update metadata for existing records}
                            {--prune        : Prune DB rows whose files no longer exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync new files, update metadata, and prune missing files in the media table.';

    public function handle()
    {
        $disk = $this->option('disk');
        $directory = rtrim($this->option('dir'), '/');

        $this->info("Starting sync on disk '{$disk}' in '{$directory}'...");

        $files = Storage::disk($disk)->allFiles($directory);

        $imported = 0;
        $updated = 0;
        $pruned = 0;

        // IMPORT new files
        foreach ($files as $path) {
            // Skip files that start with a dot (hidden files)
            if (basename($path)[0] === '.') {
                $this->line("[Skipped] {$path} (hidden file)");

                continue;
            }

            if (Media::where('disk', $disk)->where('path', $path)->exists()) {
                continue;
            }

            $fullPath = Storage::disk($disk)->path($path);
            [$width, $height] = @getimagesize($fullPath) ?: [null, null];
            $size = Storage::disk($disk)->size($path);
            $mime = Storage::disk($disk)->mimeType($path);
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $name = pathinfo($path, PATHINFO_FILENAME);

            // Always use filename as title on import
            $title = $name;

            // Read EXIF if available for storing
            $exifJson = null;
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'tiff'], true)) {
                $exifData = @exif_read_data($fullPath);
                $exifJson = $exifData ? @json_encode($exifData) : null;
            }

            Media::create([
                'disk' => $disk,
                'directory' => $directory,
                'visibility' => 'public',
                'name' => $name,
                'path' => $path,
                'width' => $width,
                'height' => $height,
                'size' => $size,
                'type' => $mime,
                'ext' => $ext,
                'alt' => null,
                'title' => $title,
                'description' => null,
                'caption' => null,
                'exif' => $exifJson,
                'curations' => null,
                'tenant_id' => null,
            ]);

            $imported++;
            $this->line("[Imported] {$path}");
        }

        // UPDATE metadata and title for existing records
        if ($this->option('update')) {
            $this->info('Updating metadata for existing records...');

            Media::where('disk', $disk)
                ->where('directory', $directory)
                ->chunk(100, function ($records) use ($disk, &$updated) {
                    foreach ($records as $media) {
                        $path = $media->path;

                        if (! Storage::disk($disk)->exists($path)) {
                            continue;
                        }

                        $fullPath = Storage::disk($disk)->path($path);
                        [$w, $h] = @getimagesize($fullPath) ?: [null, null];
                        $size = Storage::disk($disk)->size($path);
                        $mime = Storage::disk($disk)->mimeType($path);
                        $ext = pathinfo($path, PATHINFO_EXTENSION);
                        $name = pathinfo($path, PATHINFO_FILENAME);

                        // Only set title if it's not already provided
                        $title = $media->title ?: $name;

                        $exifJson = null;
                        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'tiff'], true)) {
                            $exifData = @exif_read_data($fullPath);
                            $exifJson = $exifData ? @json_encode($exifData) : null;
                        }

                        $media->update([
                            'width' => $w,
                            'height' => $h,
                            'size' => $size,
                            'type' => $mime,
                            'ext' => $ext,
                            'exif' => $exifJson,
                            'title' => $title,
                        ]);

                        $updated++;
                        $this->line("[Updated] {$path}");
                    }
                });
        }

        // PRUNE missing files
        if ($this->option('prune')) {
            $this->info('Pruning database rows for missing files...');

            Media::where('disk', $disk)
                ->where('directory', $directory)
                ->chunk(100, function ($records) use ($disk, &$pruned) {
                    foreach ($records as $media) {
                        if (! Storage::disk($disk)->exists($media->path)) {
                            $media->delete();
                            $pruned++;
                            $this->line("[Pruned] {$media->path}");
                        }
                    }
                });
        }

        // Summary
        $this->info("Sync complete: {$imported} imported, {$updated} updated, {$pruned} pruned.");

        return 0;
    }
}
