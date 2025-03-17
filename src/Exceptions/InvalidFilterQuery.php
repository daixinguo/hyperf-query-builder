<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Exceptions;

use Hyperf\Collection\Collection;
use InvalidArgumentException;

class InvalidFilterQuery extends InvalidArgumentException
{
    public static function filtersNotAllowed(Collection $unknownFilters, Collection $allowedFilters): self
    {
        $allowedFilterNames = $allowedFilters->implode(', ');
        $unknownFilterNames = $unknownFilters->implode(', ');

        return new static("Requested filter(s) `{$unknownFilterNames}` are not allowed. Allowed filter(s) are `{$allowedFilterNames}`.");
    }
}
