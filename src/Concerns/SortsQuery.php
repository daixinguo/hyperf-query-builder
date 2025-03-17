<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Concerns;

use Hyperf\Collection\Collection;
use ApiElf\QueryBuilder\AllowedSort;
use Hyperf\Stringable\Str;

trait SortsQuery
{
    /** @var \Hyperf\Collection\Collection */
    protected $allowedSorts;

    public function allowedSorts($sorts): static
    {
        $sorts = is_array($sorts) ? $sorts : func_get_args();

        $this->allowedSorts = collect($sorts)->map(function ($sort) {
            if ($sort instanceof AllowedSort) {
                return $sort;
            }

            return AllowedSort::field($sort);
        });

        $this->addSortsToQuery();

        return $this;
    }

    /**
     * 添加默认排序
     *
     * @param array|string|\ApiElf\QueryBuilder\AllowedSort $sorts
     *
     * @return \ApiElf\QueryBuilder\QueryBuilder
     */
    public function defaultSort($sorts): static
    {
        return $this->defaultSorts(func_get_args());
    }

    /**
     * 添加多个默认排序
     *
     * @param array|string|\ApiElf\QueryBuilder\AllowedSort $sorts
     *
     * @return \ApiElf\QueryBuilder\QueryBuilder
     */
    public function defaultSorts($sorts): static
    {
        if ($this->request->sorts()->isNotEmpty()) {
            // 已有请求的排序，不需要应用默认排序
            return $this;
        }

        $sorts = is_array($sorts) ? $sorts : func_get_args();

        collect($sorts)
            ->map(function ($sort) {
                if ($sort instanceof AllowedSort) {
                    return $sort;
                }

                return AllowedSort::field($sort);
            })
            ->each(function (AllowedSort $sort) {
                $sort->sort($this);
            });

        return $this;
    }

    protected function addSortsToQuery(): void
    {
        $this->request
            ->sorts()
            ->each(function (string $sort) {
                $descending = Str::startsWith($sort, '-');

                $key = ltrim($sort, '-');

                $this->addSort($key, $descending);
            });
    }

    protected function addSort(string $key, bool $descending = false): void
    {
        $sortInstance = $this->findSort($key);

        if (! $sortInstance) {
            return;
        }

        $sortInstance->sort($this, $descending);
    }

    protected function findSort(string $property): ?AllowedSort
    {
        return $this->allowedSorts
            ->first(function (AllowedSort $sort) use ($property) {
                return $sort->isForSort($property);
            });
    }
}
