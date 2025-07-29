<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

use ApiElf\QueryBuilder\QueryBuilder;
use ApiElf\QueryBuilder\Enums\FilterOperator;
use Hyperf\Database\Model\Relations\Relation;
use Hyperf\Collection\Collection;
use Hyperf\Stringable\Str;
use Hyperf\Database\Model\Builder;

class FiltersOperator implements Filter
{
    use IgnoresValueTrait;

    /** @var bool */
    protected $addRelationConstraint;

    /** @var FilterOperator */
    protected $operator;

    /** @var string */
    protected $boolean;

    /** @var array */
    protected $relationConstraints = [];

    public function __construct(
        bool $addRelationConstraint = true,
        FilterOperator $operator = FilterOperator::EQUAL,
        string $boolean = 'and'
    ) {
        $this->addRelationConstraint = $addRelationConstraint;
        $this->operator = $operator;
        $this->boolean = $boolean;
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
        $this->applyOperatorFilter($query, $value, $property);
    }

    /**
     * 应用操作符过滤
     */
    protected function applyOperatorFilter(QueryBuilder $query, mixed $value, string $property): void
    {
        if (is_array($value)) {
            $this->applyArrayFilter($query, $value, $property);
            return;
        }

        $operator = $this->operator->value;
        
        if ($this->operator->isDynamic()) {
            // 动态操作符，根据值类型自动选择
            $operator = $this->determineDynamicOperator($value);
        }

        $method = $this->boolean === 'or' ? 'orWhere' : 'where';
        $query->$method($property, $operator, $value);
    }

    /**
     * 处理数组值过滤
     */
    protected function applyArrayFilter(QueryBuilder $query, array $values, string $property): void
    {
        $method = $this->boolean === 'or' ? 'orWhere' : 'where';
        
        if ($this->operator === FilterOperator::EQUAL) {
            $query->whereIn($property, $values);
            return;
        }

        if ($this->operator === FilterOperator::NOT_EQUAL) {
            $query->whereNotIn($property, $values);
            return;
        }

        // 对于其他操作符，使用 OR 条件组合
        $query->$method(function ($query) use ($values, $property) {
            foreach ($values as $value) {
                $operator = $this->operator->isDynamic() 
                    ? $this->determineDynamicOperator($value)
                    : $this->operator->value;
                    
                $query->orWhere($property, $operator, $value);
            }
        });
    }

    /**
     * 确定动态操作符
     */
    protected function determineDynamicOperator(mixed $value): string
    {
        if (is_string($value) && (Str::contains($value, '%') || Str::contains($value, '*'))) {
            return 'LIKE';
        }

        return '=';
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
            $queryBuilder = new QueryBuilder($query);
            $this->applyOperatorFilter($queryBuilder, $value, $property);
        });
    }
}
