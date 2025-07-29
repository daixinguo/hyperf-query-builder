<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

trait IgnoresValueTrait
{
    /**
     * @param mixed $value
     */
    protected function isIgnoredValue(mixed $value): bool
    {
        // 现在忽略值的逻辑在 AllowedFilter 中处理
        // 这里只是为了保持兼容性，总是返回 false
        // @phpstan-ignore-next-line
        return false;
    }
}
