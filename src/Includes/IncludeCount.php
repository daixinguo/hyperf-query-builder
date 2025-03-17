<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Includes;

use ApiElf\QueryBuilder\QueryBuilder;

class IncludeCount implements IncludeInterface
{
    /** @var string */
    protected $relationship;

    public function __construct(string $relationship)
    {
        $this->relationship = $relationship;
    }

    public function __invoke(QueryBuilder $query)
    {
        $query->withCount($this->relationship);
    }
}
