<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

use ApiElf\QueryBuilder\QueryBuilder;
use Hyperf\Database\Model\Builder;
use Hyperf\Collection\Collection;

class FiltersPartial extends FiltersExact implements Filter
{
    public function __invoke(QueryBuilder $query, $value, string $property)
    {
        if ($this->isIgnoredValue($value)) {
            return;
        }

        // 处理关联关系属性
        if ($this->addRelationConstraint) {
            $builder = $query->getQuerybuilder();
            if ($this->isRelationProperty($builder, $property)) {
                $this->withRelationConstraint($builder, $value, $property);
                return;
            }
        }

        // 常规属性模糊匹配
        if (is_array($value)) {
            $query->where(function (QueryBuilder $query) use ($value, $property) {
                foreach ($value as $partialValue) {
                    if (!$this->isIgnoredValue($partialValue)) {
                        $query->orWhere($property, 'LIKE', "%{$partialValue}%");
                    }
                }
            });

            return;
        }

        $query->where($property, 'LIKE', "%{$value}%");
    }

    /**
     * 重写关联约束处理，使用模糊匹配
     */
    protected function withRelationConstraint(Builder $query, mixed $value, string $property): void
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(function (Collection $parts) {
                return [
                    $parts->except($parts->count() - 1)->implode('.'),
                    $parts->last(),
                ];
            });

        $query->whereHas($relation, function (Builder $query) use ($property, $value) {
            $this->relationConstraints[] = $property = $query->qualifyColumn($property);

            // 使用模糊匹配
            if (is_array($value)) {
                $query->where(function (Builder $query) use ($value, $property) {
                    foreach ($value as $partialValue) {
                        if (!$this->isIgnoredValue($partialValue)) {
                            $query->orWhere($property, 'LIKE', "%{$partialValue}%");
                        }
                    }
                });
                return;
            }

            $query->where($property, 'LIKE', "%{$value}%");
        });
    }
}
