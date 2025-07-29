<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Enums;

enum FilterOperator: string
{
    case DYNAMIC = '';
    case EQUAL = '=';
    case LESS_THAN = '<';
    case GREATER_THAN = '>';
    case LESS_THAN_OR_EQUAL = '<=';
    case GREATER_THAN_OR_EQUAL = '>=';
    case NOT_EQUAL = '<>';

    public function isDynamic(): bool
    {
        return self::DYNAMIC === $this;
    }
}
