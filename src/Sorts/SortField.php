<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Sorts;

use ApiElf\QueryBuilder\QueryBuilder;

class SortField implements Sort
{
    public function __construct() {}

    public function __invoke(QueryBuilder $query, bool $descending = false, string $property = '')
    {
        $direction = $descending ? 'desc' : 'asc';

        $query->orderBy($property, $direction);
    }
}
