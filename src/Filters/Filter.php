<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

use ApiElf\QueryBuilder\QueryBuilder;

interface Filter
{
    /**
     * @param QueryBuilder $query
     * @param mixed $value
     * @param string $property
     */
    public function __invoke(QueryBuilder $query, $value, string $property);
}
