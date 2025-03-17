<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder;

use ApiElf\QueryBuilder\Sorts\Sort;
use ApiElf\QueryBuilder\Sorts\SortField;
use ApiElf\QueryBuilder\Sorts\SortsCallback;
use ApiElf\QueryBuilder\Exceptions\InvalidDirection;

class AllowedSort
{
    public const ASCENDING = 'asc';
    public const DESCENDING = 'desc';

    protected string $defaultDirection;
    protected string $internalName;

    public function __construct(protected string $name, protected Sort $sort, ?string $internalName = null)
    {
        $this->name = ltrim($name, '-');
        $this->defaultDirection = static::parseSortDirection($name);
        $this->internalName = $internalName ?? $this->name;
    }

    public static function parseSortDirection(string $name): string
    {
        return str_starts_with($name, '-') ? self::DESCENDING : self::ASCENDING;
    }

    public static function field(string $name, ?string $internalName = null): self
    {
        return new static($name, new SortField(), $internalName);
    }

    public static function custom(string $name, Sort $sort, ?string $internalName = null): self
    {
        return new static($name, $sort, $internalName);
    }

    public static function callback(string $name, $callback, ?string $internalName = null): self
    {
        return new static($name, new SortsCallback($callback), $internalName);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isForSort(string $sortName): bool
    {
        return $this->name === $sortName;
    }

    public function getInternalName(): string
    {
        return $this->internalName;
    }

    public function sort(QueryBuilder $query, ?bool $descending = null): void
    {
        $descending = $descending ?? ($this->defaultDirection === self::DESCENDING);

        $this->sort->__invoke($query, $descending, $this->internalName);
    }

    public function defaultDirection(string $defaultDirection): static
    {
        if (! in_array($defaultDirection, [
            self::ASCENDING,
            self::DESCENDING,
        ])) {
            throw new InvalidDirection("Invalid direction `{$defaultDirection}`. Allowed directions: " . self::ASCENDING . ', ' . self::DESCENDING);
        }

        $this->defaultDirection = $defaultDirection;

        return $this;
    }
}
