<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Exceptions;

use Exception;

class InvalidFilterValue extends Exception
{
    public static function make(mixed $value): self
    {
        $valueType = gettype($value);
        $valueString = is_scalar($value) ? (string) $value : $valueType;
        
        return new static("Invalid filter value: '{$valueString}' of type '{$valueType}'.");
    }
}
