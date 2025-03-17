<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

use ApiElf\QueryBuilder\QueryBuilder;

class FiltersPartial implements Filter
{
    use IgnoresValueTrait;

    /** @var string */
    protected $internalName;

    public function __construct(string $internalName)
    {
        $this->internalName = $internalName;
    }

    public function __invoke(QueryBuilder $query, $value, string $property)
    {
        if ($this->isIgnoredValue($value)) {
            return;
        }

        if (is_array($value)) {
            $query->where(function (QueryBuilder $query) use ($value) {
                foreach ($value as $partialValue) {
                    $query->orWhere($this->internalName, 'LIKE', "%{$partialValue}%");
                }
            });

            return;
        }

        $query->where($this->internalName, 'LIKE', "%{$value}%");
    }
}
