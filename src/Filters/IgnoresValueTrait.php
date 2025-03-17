<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

trait IgnoresValueTrait
{
    /** @var array */
    protected $ignoredValues = [];

    /** @var bool */
    protected $ignoreStrictCheck = false;

    /**
     * @param mixed $values
     * @param bool $isStrict
     */
    public function ignore($values, bool $isStrict = false): Filter
    {
        $values = is_array($values) ? $values : [$values];

        $this->ignoredValues = array_merge($this->ignoredValues, $values);
        $this->ignoreStrictCheck = $isStrict;

        return $this;
    }

    /**
     * @param mixed $value
     */
    protected function isIgnoredValue($value): bool
    {
        if (is_array($value)) {
            return false;
        }

        return in_array($value, $this->ignoredValues, $this->ignoreStrictCheck);
    }
}
