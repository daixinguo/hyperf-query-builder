<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder;

use ApiElf\QueryBuilder\Includes\IncludeInterface;
use ApiElf\QueryBuilder\Includes\IncludeRelationship;
use ApiElf\QueryBuilder\Includes\IncludeCount;

class AllowedInclude
{
    /** @var string */
    protected $name;

    /** @var \Hyperf\QueryBuilder\Includes\IncludeInterface */
    protected $include;

    public function __construct(string $name, IncludeInterface $include)
    {
        $this->name = $name;
        $this->include = $include;
    }

    public static function relationship(string $name, ?string $internalName = null): self
    {
        $internalName = $internalName ?? $name;

        return new static($name, new IncludeRelationship($internalName));
    }

    public static function count(string $name, ?string $internalName = null): self
    {
        $internalName = $internalName ?? $name;

        return new static($name, new IncludeCount($internalName));
    }

    public static function custom(string $name, IncludeInterface $includeClass): self
    {
        return new static($name, $includeClass);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isForInclude(string $includeName): bool
    {
        return $this->name === $includeName;
    }

    public function include(QueryBuilder $query): void
    {
        $this->include->__invoke($query);
    }
}
