<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

use ApiElf\QueryBuilder\QueryBuilder;
use Hyperf\Database\Model\Relations\Relation;
use Hyperf\Collection\Collection;
use Hyperf\Stringable\Str;
use Hyperf\Database\Model\Builder;

class FiltersBeginsWithStrict implements Filter
{
    use IgnoresValueTrait;

    /** @var bool */
    protected $addRelationConstraint;

    /** @var array */
    protected $relationConstraints = [];

    public function __construct(bool $addRelationConstraint = true)
    {
        $this->addRelationConstraint = $addRelationConstraint;
    }

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

        // 常规属性过滤
        if (is_array($value)) {
            $query->where(function ($query) use ($property, $value) {
                foreach ($value as $singleValue) {
                    $query->orWhere($property, 'LIKE', $singleValue . '%');
                }
            });
            return;
        }

        $query->where($property, 'LIKE', $value . '%');
    }

    /**
     * 判断属性是否为关联关系属性
     */
    protected function isRelationProperty(Builder $query, string $property): bool
    {
        if (! Str::contains($property, '.')) {
            return false;
        }

        if (in_array($property, $this->relationConstraints)) {
            return false;
        }

        $firstRelationship = explode('.', $property)[0];

        if (! method_exists($query->getModel(), $firstRelationship)) {
            return false;
        }

        return is_a($query->getModel()->{$firstRelationship}(), Relation::class);
    }

    /**
     * 处理关联关系约束
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

            // 递归调用自身处理嵌套关联
            if (is_array($value)) {
                $query->where(function ($query) use ($property, $value) {
                    foreach ($value as $singleValue) {
                        $query->orWhere($property, 'LIKE', $singleValue . '%');
                    }
                });
                return;
            }

            $query->where($property, 'LIKE', $value . '%');
        });
    }
}
