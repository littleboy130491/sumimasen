<?php

namespace Littleboy130491\Sumimasen\Filament\Imports;

use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Models\Page;
use Littleboy130491\Sumimasen\Models\User;
use Awcodes\Curator\Models\Media;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PageImporter extends Importer
{
    protected static ?string $model = Page::class;

    public static function getColumns(): array
    {
        $columns = [
            ImportColumn::make('id')
                ->label('ID')
                ->ignoreBlankState(),

            ImportColumn::make('status')
                ->castStateUsing(function (?string $state): ?ContentStatus {
                    if (empty($state)) {
                        return ContentStatus::Draft;
                    }

                    return match (strtolower($state)) {
                        'published' => ContentStatus::Published,
                        'scheduled' => ContentStatus::Scheduled,
                        'draft' => ContentStatus::Draft,
                        default => ContentStatus::Draft,
                    };
                }),

            ImportColumn::make('template'),

            ImportColumn::make('menu_order')
                ->numeric()
                ->castStateUsing(fn(?string $state): int => (int) ($state ?? 0)),

            ImportColumn::make('author')
                ->label('Author (Name or Email)')
                ->castStateUsing(function (?string $state): ?int {
                    if (empty($state)) {
                        return auth()->id();
                    }

                    // Try to find by email first
                    $user = User::where('email', $state)->first();
                    if (!$user) {
                        // Try to find by name
                        $user = User::where('name', $state)->first();
                    }

                    return $user?->id ?? auth()->id();
                })
                ->fillRecordUsing(function (Page $record, $state): void {
                    $record->author_id = $state;
                }),

            ImportColumn::make('parent_page')
                ->label('Parent Page (Title or Slug)')
                ->castStateUsing(function (?string $state): ?int {
                    if (empty($state)) {
                        return null;
                    }

                    // Try to find by title in any language
                    $parentPage = Page::whereJsonContains('title', $state)
                        ->orWhereJsonContains('slug', $state)
                        ->first();

                    return $parentPage?->id;
                })
                ->fillRecordUsing(function (Page $record, $state): void {
                    $record->parent_id = $state;
                }),

            ImportColumn::make('featured_image')
                ->label('Featured Image (ID or URL)')
                ->castStateUsing(function (?string $state): ?string {
                    if (empty($state)) {
                        return null;
                    }

                    // If it's numeric, assume it's an ID
                    if (is_numeric($state)) {
                        return $state;
                    }

                    // Otherwise, try to find by filename
                    $filename = basename($state);
                    $media = Media::where('filename', $filename)
                        ->orWhere('path', 'like', "%{$filename}")
                        ->first();

                    return $media?->id;
                }),

            ImportColumn::make('published_at')
                ->castStateUsing(function (?string $state): ?Carbon {
                    if (empty($state)) {
                        return null;
                    }

                    try {
                        return Carbon::parse($state);
                    } catch (\Exception $e) {
                        return null;
                    }
                }),

            ImportColumn::make('created_at')
                ->castStateUsing(function (?string $state): ?Carbon {
                    if (empty($state)) {
                        return null;
                    }

                    try {
                        return Carbon::parse($state);
                    } catch (\Exception $e) {
                        return null;
                    }
                }),

            ImportColumn::make('updated_at')
                ->castStateUsing(function (?string $state): ?Carbon {
                    if (empty($state)) {
                        return null;
                    }

                    try {
                        return Carbon::parse($state);
                    } catch (\Exception $e) {
                        return null;
                    }
                }),

            ImportColumn::make('custom_fields')
                ->castStateUsing(function (?string $state): ?array {
                    if (empty($state)) {
                        return null;
                    }

                    return json_decode($state, true);
                }),
        ];

        // Get available locales
        $availableLocales = array_keys(config('cms.language_available', ['en' => 'English']));
        $translatableAttributes = ['title', 'slug', 'content', 'excerpt', 'section'];

        // For each translatable attribute, create a handler that can merge all language columns
        foreach ($translatableAttributes as $attribute) {
            // Create a single import column that will handle all language variations
            $columns[] = ImportColumn::make($attribute)
                ->label(ucfirst($attribute))
                ->requiredMapping($attribute === 'title' || $attribute === 'slug')
                ->rules($attribute === 'title' || $attribute === 'slug' ? ['required'] : [])
                ->fillRecordUsing(function (Page $record, $state, array $data) use ($attribute, $availableLocales): void {
                    $translations = [];

                    // First, check if there's a JSON value in the base column
                    if (!empty($data[$attribute])) {
                        $baseValue = $data[$attribute];
                        if (str_starts_with($baseValue, '{') && str_ends_with($baseValue, '}')) {
                            $decoded = json_decode($baseValue, true);
                            if (is_array($decoded)) {
                                $translations = $decoded;
                            }
                        } else {
                            // If it's a simple string, assign to default locale
                            $translations[config('app.locale')] = $baseValue;
                        }
                    }

                    // Then check for language-specific columns (e.g., title_en, title_id)
                    foreach ($availableLocales as $locale) {
                        $columnName = "{$attribute}_{$locale}";
                        if (isset($data[$columnName]) && !empty($data[$columnName])) {
                            $value = $data[$columnName];

                            // Handle section JSON data
                            if ($attribute === 'section' && str_starts_with($value, '[')) {
                                $value = json_decode($value, true);
                            }

                            $translations[$locale] = $value;
                        }
                    }

                    // Generate slug if needed and not provided
                    if ($attribute === 'slug' && empty($translations)) {
                        foreach ($availableLocales as $locale) {
                            $titleColumn = "title_{$locale}";
                            if (!empty($data[$titleColumn])) {
                                $translations[$locale] = Str::slug($data[$titleColumn]);
                            } elseif (!empty($data['title'])) {
                                $translations[$locale] = Str::slug($data['title']);
                                break; // Use the same slug for all if only base title is provided
                            }
                        }
                    }

                    // Set the translations on the record
                    if (!empty($translations)) {
                        $record->setTranslations($attribute, $translations);
                    }
                });
        }

        // Add placeholder columns for each language variation (these won't actually process data)
        foreach ($translatableAttributes as $attribute) {
            foreach ($availableLocales as $locale) {
                $columns[] = ImportColumn::make("{$attribute}_{$locale}")
                    ->label(ucfirst($attribute) . ' (' . strtoupper($locale) . ')')
                    ->ignoreBlankState()
                    ->fillRecordUsing(fn() => null); // No-op, handled by the base column
            }
        }

        return $columns;
    }

    public function resolveRecord(): ?Page
    {
        // Try to find existing record by ID first
        if (!empty($this->data['id'])) {
            $existingPage = Page::find($this->data['id']);
            if ($existingPage) {
                return $existingPage;
            }
        }

        // Check for existing page by slug (in any language)
        $availableLocales = array_keys(config('cms.language_available', ['en' => 'English']));

        // Check language-specific slug columns
        foreach ($availableLocales as $locale) {
            $slugColumn = "slug_{$locale}";
            if (!empty($this->data[$slugColumn])) {
                $existingPage = Page::whereJsonContains('slug->' . $locale, $this->data[$slugColumn])->first();
                if ($existingPage) {
                    return $existingPage;
                }
            }
        }

        // Check base slug column with JSON
        if (!empty($this->data['slug'])) {
            $slugData = $this->data['slug'];
            if (str_starts_with($slugData, '{')) {
                $slugs = json_decode($slugData, true);
                if (is_array($slugs)) {
                    foreach ($slugs as $locale => $slugValue) {
                        $existingPage = Page::whereJsonContains('slug->' . $locale, $slugValue)->first();
                        if ($existingPage) {
                            return $existingPage;
                        }
                    }
                }
            } else {
                // Simple slug string
                $existingPage = Page::whereJsonContains('slug', $slugData)->first();
                if ($existingPage) {
                    return $existingPage;
                }
            }
        }

        // Create new page if not found
        return new Page();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your Page import has completed and ' . number_format($import->successful_rows) . ' ' . Str::plural('row', $import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . Str::plural('row', $failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Forms\Components\Checkbox::make('update_existing')
                ->label('Update existing pages')
                ->helperText('If checked, existing pages with matching IDs or slugs will be updated instead of skipped.')
                ->default(false),
        ];
    }
}