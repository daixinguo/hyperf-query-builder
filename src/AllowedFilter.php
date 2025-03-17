<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder;

use Closure;
use ApiElf\QueryBuilder\Filters\Filter;
use ApiElf\QueryBuilder\Filters\FiltersCallback;
use ApiElf\QueryBuilder\Filters\FiltersExact;
use ApiElf\QueryBuilder\Filters\FiltersPartial;
use ApiElf\QueryBuilder\Filters\FiltersScope;
use ApiElf\QueryBuilder\Filters\FiltersTrashed;

class AllowedFilter
{
    /** @var string */
    protected $name;

    /** @var \ApiElf\QueryBuilder\Filters\Filter */
    protected $filter;

    /** @var mixed */
    protected $default;

    /** @var bool */
    protected $hasDefault = false;

    public function __construct(string $name, Filter $filter, mixed $default = null, bool $hasDefault = false)
    {
        $this->name = $name;
        $this->filter = $filter;
        $this->default = $default;
        $this->hasDefault = $hasDefault;
    }

    public static function exact(string $name, ?string $internalName = null, mixed $default = null, bool $hasDefault = false): self
    {
        $internalName = $internalName ?? $name;

        return new static($name, new FiltersExact($internalName), $default, $hasDefault);
    }

    public static function partial(string $name, ?string $internalName = null, mixed $default = null, bool $hasDefault = false): self
    {
        $internalName = $internalName ?? $name;

        return new static($name, new FiltersPartial($internalName), $default, $hasDefault);
    }

    public static function scope(string $name, ?string $internalName = null, mixed $default = null, bool $hasDefault = false): self
    {
        $internalName = $internalName ?? $name;

        return new static($name, new FiltersScope($internalName), $default, $hasDefault);
    }

    public static function callback(string $name, callable $callback, mixed $default = null, bool $hasDefault = false): self
    {
        return new static($name, new FiltersCallback($callback), $default, $hasDefault);
    }

    public static function trashed(string $name = 'trashed', mixed $default = null, bool $hasDefault = false): self
    {
        return new static($name, new FiltersTrashed(), $default, $hasDefault);
    }

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
        $this->filter->__invoke($query, $value, $this->name);
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
        $this->default = $value;
        $this->hasDefault = true;

        return $this;
    }

    public function ignore(mixed $values, bool $isStrict = false): self
    {
        $this->filter->ignore($values, $isStrict);

        return $this;
    }
}
