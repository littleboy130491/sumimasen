<?php

namespace Littleboy130491\Sumimasen\Filament\Builders;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Littleboy130491\Sumimasen\Enums\ContentStatus;

class TableBuilder
{
    public function __construct(
        protected string $modelClass,
        protected array $hiddenFields = [],
        protected ?object $resourceClass = null
    ) {}

    public function buildColumns(): array
    {
        $columns = [];

        if ($this->modelHasColumn('title')) {
            $columns[] = $this->buildTitleColumn();
        }

        if ($this->modelHasColumn('slug')) {
            $columns[] = TextColumn::make('slug')->limit(50);
        }

        if ($this->modelHasColumn('featured') && ! $this->isFieldHidden('featured')) {
            $columns[] = ToggleColumn::make('featured');
        }

        if ($this->modelHasColumn('status') && ! $this->isFieldHidden('status')) {
            $columns[] = TextColumn::make('status')
                ->badge()
                ->sortable();
        }

        if ($this->modelHasRelationship('author') && ! $this->isFieldHidden('author_id')) {
            $columns[] = TextColumn::make('author.name')
                ->sortable()
                ->searchable();
        }

        $columns = [...$columns, ...$this->buildAdditionalColumns()];
        $columns = [...$columns, ...$this->buildDateColumns()];

        if ($this->modelHasColumn('menu_order') && ! $this->isFieldHidden('menu_order')) {
            $columns[] = TextColumn::make('menu_order')
                ->label('Order')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        return $columns;
    }

    protected function buildTitleColumn(): TextColumn
    {
        $currentLocale = app()->getLocale();

        return TextColumn::make('title')
            ->searchable(query: function (Builder $query, string $search): Builder {
                $currentLocale = app()->getLocale();

                return $query->where(function (Builder $subQuery) use ($search, $currentLocale) {
                    $subQuery->whereRaw(
                        "LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$currentLocale}'))) LIKE LOWER(?)",
                        ["%{$search}%"]
                    );

                    if ($this->modelHasColumn('content')) {
                        $subQuery->orWhereRaw(
                            "LOWER(JSON_UNQUOTE(JSON_EXTRACT(content, '$.{$currentLocale}'))) LIKE LOWER(?)",
                            ["%{$search}%"]
                        );
                    }

                    $subQuery->orWhere('title->'.$currentLocale, 'like', "%{$search}%");
                });
            })
            ->sortable()
            ->limit(50)
            ->getStateUsing(function ($record) use ($currentLocale) {
                return $record->getTranslation('title', $currentLocale);
            });
    }

    protected function buildDateColumns(): array
    {
        $columns = [];

        if ($this->modelHasColumn('published_at') && ! $this->isFieldHidden('published_at')) {
            $columns[] = TextColumn::make('published_at')
                ->dateTime()
                ->sortable();
        }

        if ($this->modelHasColumn('created_at') && ! $this->isFieldHidden('created_at')) {
            $columns[] = TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if ($this->modelHasColumn('updated_at') && ! $this->isFieldHidden('updated_at')) {
            $columns[] = TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if ($this->modelHasColumn('deleted_at') && ! $this->isFieldHidden('deleted_at')) {
            $columns[] = TextColumn::make('deleted_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        return $columns;
    }

    protected function buildAdditionalColumns(): array
    {
        return [];
    }

    public function buildFilters(): array
    {
        return [
            Tables\Filters\TrashedFilter::make(),
        ];
    }

    public function buildActions(): array
    {
        return [
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('replicate')
                ->icon('heroicon-o-document-duplicate')
                ->action(function (Tables\Actions\Action $action, \Illuminate\Database\Eloquent\Model $record, \Livewire\Component $livewire) {
                    $newRecord = $this->resourceClass?->duplicateRecord($record);
                    $livewire->redirect($this->resourceClass::getUrl('index', ['record' => $newRecord]));
                }),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\ForceDeleteAction::make(),
            Tables\Actions\RestoreAction::make(),
        ];
    }

    public function buildBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                ...$this->buildEditBulkAction(),
                ...$this->buildExportBulkAction(),
            ]),
        ];
    }

    protected function buildEditBulkAction(): array
    {
        return [
            Tables\Actions\BulkAction::make('edit')
                ->form(function () {
                    $fields = [];

                    if ($this->modelHasColumn('status')) {
                        $fields[] = Select::make('status')
                            ->enum(ContentStatus::class)
                            ->options(ContentStatus::class)
                            ->nullable();
                    }

                    if ($this->modelHasColumn('author_id')) {
                        $fields[] = Select::make('author_id')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable();
                    }

                    if ($this->modelHasColumn('published_at')) {
                        $fields[] = DateTimePicker::make('published_at')
                            ->nullable();
                    }

                    return $fields;
                })
                ->action(function (Collection $records, array $data) {
                    $records->each(function (\Illuminate\Database\Eloquent\Model $record) use ($data) {
                        $updateData = [];
                        if (isset($data['status'])) {
                            $updateData['status'] = $data['status'];
                        }
                        if (isset($data['author_id'])) {
                            $updateData['author_id'] = $data['author_id'];
                        }
                        if (isset($data['published_at'])) {
                            $updateData['published_at'] = $data['published_at'];
                        }
                        $record->update($updateData);
                    });
                })
                ->deselectRecordsAfterCompletion()
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->label('Edit selected'),
        ];
    }

    protected function buildExportBulkAction(): array
    {
        return [];
    }

    public function buildHeaderActions(): array
    {
        return [];
    }

    protected function isFieldHidden(string $field): bool
    {
        return in_array($field, $this->hiddenFields);
    }

    protected function modelHasColumn(string $column): bool
    {
        $modelClass = app($this->modelClass);

        return in_array($column, $modelClass->getFillable()) ||
            array_key_exists($column, $modelClass->getCasts()) ||
            $modelClass->hasAttribute($column);
    }

    protected function modelHasRelationship(string $relationship): bool
    {
        $modelClass = app($this->modelClass);

        if (! method_exists($modelClass, $relationship)) {
            return false;
        }

        try {
            $result = $modelClass->{$relationship}();

            return $result instanceof \Illuminate\Database\Eloquent\Relations\Relation;
        } catch (\Exception $e) {
            return false;
        }
    }
}
