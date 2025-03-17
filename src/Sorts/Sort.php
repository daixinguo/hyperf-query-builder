<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Sorts;

use ApiElf\QueryBuilder\QueryBuilder;

interface Sort
{
    /**
     * @param QueryBuilder $query
     * @param bool $descending
     * @param string $property
     */
    public function __invoke(QueryBuilder $query, bool $descending = false, string $property = '');
}
