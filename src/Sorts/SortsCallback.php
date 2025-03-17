<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Sorts;

use ApiElf\QueryBuilder\QueryBuilder;

class SortsCallback implements Sort
{
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(QueryBuilder $query, bool $descending = false, string $property = '')
    {
        return call_user_func_array($this->callback, [$query, $descending, $property]);
    }
}
