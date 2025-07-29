<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Enums;

enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    public function isAscending(): bool
    {
        return self::ASC === $this;
    }

    public function isDescending(): bool
    {
        return self::DESC === $this;
    }
}
