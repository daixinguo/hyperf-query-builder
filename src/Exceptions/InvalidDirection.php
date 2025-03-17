<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Exceptions;

use InvalidArgumentException;

class InvalidDirection extends InvalidArgumentException
{
    public static function make(string $direction): self
    {
        return new static("Invalid direction `{$direction}`. Allowed directions: asc, desc");
    }
}
