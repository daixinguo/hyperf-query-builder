<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Concerns;

use Hyperf\Collection\Collection;
use ApiElf\QueryBuilder\AllowedInclude;

trait AddsIncludesToQuery
{
    /** @var \Hyperf\Collection\Collection */
    protected $allowedIncludes;

    public function allowedIncludes($includes): static
    {
        $includes = is_array($includes) ? $includes : func_get_args();

        $this->allowedIncludes = collect($includes)->map(function ($include) {
            if ($include instanceof AllowedInclude) {
                return $include;
            }

            return AllowedInclude::relationship($include);
        });

        $this->addIncludesToQuery();

        return $this;
    }

    protected function addIncludesToQuery(): void
    {
        $this->request->includes()
            ->filter(function (string $include) {
                return $this->isIncludeAllowed($include);
            })
            ->each(function (string $include) {
                $this->addInclude($include);
            });
    }

    protected function addInclude(string $include): void
    {
        $include = $this->findInclude($include);

        if (! $include) {
            return;
        }

        $include->include($this);
    }

    protected function findInclude(string $include): ?AllowedInclude
    {
        return $this->allowedIncludes
            ->first(function (AllowedInclude $allowedInclude) use ($include) {
                return $allowedInclude->isForInclude($include);
            });
    }

    protected function isIncludeAllowed(string $include): bool
    {
        return $this->allowedIncludes
            ->contains(function (AllowedInclude $allowed) use ($include) {
                return $allowed->isForInclude($include);
            });
    }
}
