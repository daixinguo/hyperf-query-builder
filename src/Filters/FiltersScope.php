<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

use ApiElf\QueryBuilder\QueryBuilder;

class FiltersScope implements Filter
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

        $scope = $this->internalName;

        if (is_array($value)) {
            $query->$scope(...$value);

            return;
        }

        $query->$scope($value);
    }
}
