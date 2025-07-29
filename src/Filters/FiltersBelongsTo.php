<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

use ApiElf\QueryBuilder\QueryBuilder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\Relation;
use Hyperf\Stringable\Str;
use Hyperf\Database\Model\Builder;

class FiltersBelongsTo implements Filter
{
    use IgnoresValueTrait;

    public function __invoke(QueryBuilder $query, $value, string $property)
    {
        if ($this->isIgnoredValue($value)) {
            return;
        }

        $builder = $query->getQuerybuilder();
        
        // 检查是否为关联属性
        if (Str::contains($property, '.')) {
            $this->filterNestedRelation($builder, $value, $property);
            return;
        }

        // 检查是否为 BelongsTo 关联
        if (! method_exists($builder->getModel(), $property)) {
            // 如果不是关联方法，则按普通字段处理
            $this->filterByColumn($builder, $value, $property);
            return;
        }

        $relation = $builder->getModel()->{$property}();
        
        if (! $relation instanceof BelongsTo) {
            // 如果不是 BelongsTo 关联，则按普通字段处理
            $this->filterByColumn($builder, $value, $property);
            return;
        }

        // 处理 BelongsTo 关联
        $this->filterBelongsToRelation($builder, $value, $relation);
    }

    /**
     * 处理嵌套关联过滤
     */
    protected function filterNestedRelation(Builder $query, mixed $value, string $property): void
    {
        $parts = explode('.', $property);
        $relationName = array_shift($parts);
        $remainingProperty = implode('.', $parts);

        if (! method_exists($query->getModel(), $relationName)) {
            return;
        }

        $relation = $query->getModel()->{$relationName}();
        
        if (! $relation instanceof Relation) {
            return;
        }

        $query->whereHas($relationName, function (Builder $query) use ($value, $remainingProperty) {
            // 递归处理嵌套关联
            $filterInstance = new static();
            $queryBuilder = new QueryBuilder($query);
            $filterInstance($queryBuilder, $value, $remainingProperty);
        });
    }

    /**
     * 处理 BelongsTo 关联过滤
     */
    protected function filterBelongsToRelation(Builder $query, mixed $value, BelongsTo $relation): void
    {
        $foreignKey = $relation->getForeignKeyName();
        
        if (is_array($value)) {
            $ids = array_map([$this, 'extractModelId'], $value);
            $query->whereIn($foreignKey, array_filter($ids));
            return;
        }

        $id = $this->extractModelId($value);
        if ($id !== null) {
            $query->where($foreignKey, $id);
        }
    }

    /**
     * 按普通字段过滤
     */
    protected function filterByColumn(Builder $query, mixed $value, string $column): void
    {
        if (is_array($value)) {
            $query->whereIn($column, $value);
            return;
        }

        $query->where($column, $value);
    }

    /**
     * 从值中提取模型 ID
     */
    protected function extractModelId(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $value->getKey();
        }

        return $value;
    }
}
