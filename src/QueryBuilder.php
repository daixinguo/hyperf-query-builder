<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder;

use ArrayAccess;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Model\Builder as EloquentBuilder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\Relation;
use Hyperf\HttpServer\Request;
use Hyperf\Support\Traits\ForwardsCalls;
use ApiElf\QueryBuilder\Concerns\AddsFieldsToQuery;
use ApiElf\QueryBuilder\Concerns\AddsIncludesToQuery;
use ApiElf\QueryBuilder\Concerns\FiltersQuery;
use ApiElf\QueryBuilder\Concerns\SortsQuery;
use Psr\Container\ContainerInterface;

/**
 * @mixin EloquentBuilder
 */
class QueryBuilder implements ArrayAccess
{
    use FiltersQuery;
    use SortsQuery;
    use AddsIncludesToQuery;
    use AddsFieldsToQuery;
    use ForwardsCalls;

    protected QueryBuilderRequest $request;

    public function __construct(
        protected EloquentBuilder|Relation $subject,
        ?Request $request = null
    ) {
        if ($request) {
            $this->request = QueryBuilderRequest::fromRequest($request);
        } else {
            $container = ApplicationContext::getContainer();
            $this->request = $container->get(QueryBuilderRequest::class);
        }
    }

    public function getEloquentBuilder(): EloquentBuilder
    {
        if ($this->subject instanceof EloquentBuilder) {
            return $this->subject;
        }

        return $this->subject->getQuery();
    }

    public function getSubject(): Relation|EloquentBuilder
    {
        return $this->subject;
    }

    public static function for(
        EloquentBuilder|Relation|string $subject,
        ?Request $request = null
    ): static {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        return new static($subject, $request);
    }

    public function __call($name, $arguments)
    {
        $result = $this->forwardCallTo($this->subject, $name, $arguments);

        /*
         * If the forwarded method call is part of a chain we can return $this
         * instead of the actual $result to keep the chain going.
         */
        if ($result === $this->subject) {
            return $this;
        }

        return $result;
    }

    public function clone(): static
    {
        return clone $this;
    }

    public function __clone()
    {
        $this->subject = clone $this->subject;
    }

    public function __get($name)
    {
        return $this->subject->{$name};
    }

    public function __set($name, $value)
    {
        $this->subject->{$name} = $value;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->subject[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->subject[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->subject[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->subject[$offset]);
    }
}
