<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder;

use ApiElf\QueryBuilder\Enums\FilterOperator;
use ApiElf\QueryBuilder\Filters\Filter;
use ApiElf\QueryBuilder\Filters\FiltersBeginsWithStrict;
use ApiElf\QueryBuilder\Filters\FiltersBelongsTo;
use ApiElf\QueryBuilder\Filters\FiltersCallback;
use ApiElf\QueryBuilder\Filters\FiltersEndsWithStrict;
use ApiElf\QueryBuilder\Filters\FiltersExact;
use ApiElf\QueryBuilder\Filters\FiltersOperator;
use ApiElf\QueryBuilder\Filters\FiltersPartial;
use ApiElf\QueryBuilder\Filters\FiltersScope;
use ApiElf\QueryBuilder\Filters\FiltersTrashed;

class AllowedFilter
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $internalName;

    /** @var \ApiElf\QueryBuilder\Filters\Filter */
    protected $filter;

    /** @var \Hyperf\Collection\Collection */
    protected $ignored;

    /** @var mixed */
    protected $default;

    /** @var bool */
    protected $hasDefault = false;

    /** @var bool */
    protected $nullable = false;

    public function __construct(string $name, Filter $filter, ?string $internalName = null)
    {
        $this->name = $name;
        $this->filter = $filter;
        $this->internalName = $internalName ?? $name;
        $this->ignored = collect();
    }

    /**
     * 创建精确匹配过滤器
     */
    public static function exact(
        string $name,
        ?string $internalName = null,
        bool $addRelationConstraint = true
    ): self {
        return new static($name, new FiltersExact($addRelationConstraint), $internalName);
    }

    /**
     * 创建模糊匹配过滤器
     */
    public static function partial(
        string $name,
        ?string $internalName = null,
        bool $addRelationConstraint = true
    ): self {
        return new static($name, new FiltersPartial($addRelationConstraint), $internalName);
    }

    /**
     * 创建基于查询作用域的过滤器
     */
    public static function scope(string $name, ?string $internalName = null): self
    {
        return new static($name, new FiltersScope(), $internalName);
    }

    /**
     * 创建基于回调函数的过滤器
     */
    public static function callback(string $name, callable $callback, ?string $internalName = null): self
    {
        return new static($name, new FiltersCallback($callback), $internalName);
    }

    /**
     * 创建已删除记录过滤器
     */
    public static function trashed(string $name = 'trashed', ?string $internalName = null): self
    {
        return new static($name, new FiltersTrashed(), $internalName);
    }

    /**
     * 创建以指定字符串开头的严格匹配过滤器
     */
    public static function beginsWithStrict(
        string $name,
        ?string $internalName = null,
        bool $addRelationConstraint = true
    ): self {
        return new static($name, new FiltersBeginsWithStrict($addRelationConstraint), $internalName);
    }

    /**
     * 创建以指定字符串结尾的严格匹配过滤器
     */
    public static function endsWithStrict(
        string $name,
        ?string $internalName = null,
        bool $addRelationConstraint = true
    ): self {
        return new static($name, new FiltersEndsWithStrict($addRelationConstraint), $internalName);
    }

    /**
     * 创建 BelongsTo 关联过滤器
     */
    public static function belongsTo(
        string $name,
        ?string $internalName = null
    ): self {
        return new static($name, new FiltersBelongsTo(), $internalName);
    }

    /**
     * 创建操作符过滤器
     */
    public static function operator(
        string $name,
        FilterOperator $filterOperator,
        string $boolean = 'and',
        ?string $internalName = null,
        bool $addRelationConstraint = true
    ): self {
        return new static(
            $name,
            new FiltersOperator($addRelationConstraint, $filterOperator, $boolean),
            $internalName
        );
    }

    /**
     * 创建自定义过滤器
     */
    public static function custom(string $name, Filter $filter, mixed $default = null, bool $hasDefault = false): self
    {
        return new static($name, $filter, $default, $hasDefault);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isForFilter(string $filterName): bool
    {
        return $this->name === $filterName;
    }

    public function filter(QueryBuilder $query, mixed $value): void
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        if (! $this->nullable && is_null($valueToFilter)) {
            return;
        }

        $this->filter->__invoke($query, $valueToFilter, $this->internalName);
    }

    public function getFilter(): Filter
    {
        return $this->filter;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function default(mixed $value): self
    {
        $this->hasDefault = true;
        $this->default = $value;

        if (is_null($value)) {
            $this->nullable(true);
        }

        return $this;
    }

    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function unsetDefault(): self
    {
        $this->hasDefault = false;
        unset($this->default);

        return $this;
    }

    public function ignore(...$values): self
    {
        $this->ignored = $this->ignored
            ->merge($values)
            ->flatten();

        return $this;
    }

    public function getIgnored(): array
    {
        return $this->ignored->toArray();
    }

    public function getInternalName(): string
    {
        return $this->internalName;
    }

    protected function resolveValueForFiltering($value)
    {
        if (is_array($value)) {
            $remainingProperties = array_map([$this, 'resolveValueForFiltering'], $value);

            return ! empty($remainingProperties) ? $remainingProperties : null;
        }

        return ! $this->ignored->contains($value) ? $value : null;
    }
}
