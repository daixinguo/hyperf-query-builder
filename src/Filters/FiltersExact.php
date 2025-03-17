<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

use Hyperf\Collection\Arr;
use ApiElf\QueryBuilder\QueryBuilder;

class FiltersExact implements Filter
{
    use IgnoresValueTrait;

    /** @var string */
    protected $internalName;

    public function __construct(string $internalName)
    {
        $this->internalName = $internalName;
    }

    public function __invoke(QueryBuilder $query, $value, string $property)
    {
        if ($this->isIgnoredValue($value)) {
            return;
        }

        if (is_array($value)) {
            $query->whereIn($this->internalName, $value);

            return;
        }

        $query->where($this->internalName, '=', $value);
    }
}
