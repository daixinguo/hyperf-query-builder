<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Includes;

use ApiElf\QueryBuilder\QueryBuilder;

interface IncludeInterface
{
    /**
     * @param QueryBuilder $query
     */
    public function __invoke(QueryBuilder $query);
}
