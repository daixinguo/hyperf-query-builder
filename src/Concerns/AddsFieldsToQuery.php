<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Concerns;

use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder as EloquentBuilder;
use Hyperf\Database\Model\Relations\Relation;
use Hyperf\Database\Query\Builder as QueryBuilder;

trait AddsFieldsToQuery
{
    protected array $allowedFields = [];

    public function allowedFields($fields): static
    {
        $fields = is_array($fields) ? $fields : func_get_args();

        $this->allowedFields = array_merge($this->allowedFields, $fields);

        $this->addFieldsToQuery();

        return $this;
    }

    protected function addFieldsToQuery(): void
    {
        $fields = $this->request->fields();

        if ($fields->isEmpty()) {
            return;
        }

        $modelTableName = $this->getSubject()->getModel()->getTable();

        if ($modelFields = $this->getFieldsForModel($fields, $modelTableName)) {
            $this->getSubject()->select($this->prependFieldsWithTableName($modelFields, $modelTableName));
        }

        $this->addFieldsForRelatedTables($fields);
    }

    protected function getFieldsForModel(Collection $fields, string $modelTableName): array
    {
        $fieldsForModel = $fields->get('_', []);

        if (! $fieldsForModel) {
            $fieldsForModel = $fields->get($modelTableName, []);
        }

        $fieldsForModel = array_intersect($fieldsForModel, $this->allowedFields);

        return $fieldsForModel;
    }

    protected function addFieldsForRelatedTables(Collection $fields): void
    {
        $fields->reject(function ($fields, $model) {
            return $model === '_';
        })->each(function (array $tableFields, string $relation) {
            $tableFields = array_intersect($tableFields, $this->allowedFields);

            if (! count($tableFields)) {
                return;
            }

            $this->getSubject()->with([
                $relation => function ($query) use ($tableFields, $relation) {
                    $query->select($this->prependFieldsWithTableName($tableFields, $query->getModel()->getTable()));
                },
            ]);
        });
    }

    protected function prependFieldsWithTableName(array $fields, string $tableName): array
    {
        return array_map(function ($field) use ($tableName) {
            return "{$tableName}.{$field}";
        }, $fields);
    }

    protected function getSubject(): EloquentBuilder|Relation
    {
        return $this->subject;
    }
}
