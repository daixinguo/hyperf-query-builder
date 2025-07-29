<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Filters;

use ApiElf\QueryBuilder\QueryBuilder;
use ApiElf\QueryBuilder\Exceptions\InvalidFilterValue;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Builder;
use Hyperf\Stringable\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionParameter;
use ReflectionUnionType;

class FiltersScope implements Filter
{
    use IgnoresValueTrait;

    public function __invoke(QueryBuilder $query, $value, string $property)
    {
        if ($this->isIgnoredValue($value)) {
            return;
        }

        $propertyParts = collect(explode('.', $property));
        $scope = Str::camel($propertyParts->pop());

        $values = array_values(is_array($value) ? $value : [$value]);
        $values = $this->resolveParameters($query->getQuerybuilder(), $values, $scope);

        $relation = $propertyParts->implode('.');

        if ($relation) {
            $query->whereHas($relation, function (Builder $query) use ($scope, $values) {
                return $query->$scope(...$values);
            });
            return;
        }

        $query->$scope(...$values);
    }

    /**
     * 解析作用域方法的参数
     */
    protected function resolveParameters(Builder $query, array $values, string $scope): array
    {
        try {
            $parameters = (new ReflectionObject($query->getModel()))
                ->getMethod('scope' . ucfirst($scope))
                ->getParameters();
        } catch (ReflectionException) {
            return $values;
        }

        foreach ($parameters as $parameter) {
            if (! $this->getClass($parameter)?->isSubclassOf(Model::class)) {
                continue;
            }

            /** @var Model $model */
            $model = $this->getClass($parameter)->newInstance();
            $index = $parameter->getPosition() - 1;

            if (! isset($values[$index])) {
                continue;
            }

            $value = $values[$index];

            $result = $this->resolveRouteBinding($model, $value);

            if ($result === null) {
                throw InvalidFilterValue::make($value);
            }

            $values[$index] = $result;
        }

        return $values;
    }

    /**
     * 解析路由绑定
     */
    protected function resolveRouteBinding(Model $model, mixed $value): ?Model
    {
        if ($value instanceof Model) {
            return $value;
        }

        // 尝试通过主键查找模型
        if (is_scalar($value)) {
            return $model->newQuery()->find($value);
        }

        return null;
    }

    /**
     * 获取参数的类反射
     */
    protected function getClass(ReflectionParameter $parameter): ?ReflectionClass
    {
        $type = $parameter->getType();

        if (is_null($type)) {
            return null;
        }

        if ($type instanceof ReflectionUnionType) {
            return null;
        }

        if ($type->isBuiltin()) {
            return null;
        }

        if ($type->getName() === 'self') {
            return $parameter->getDeclaringClass();
        }

        return new ReflectionClass($type->getName());
    }
}
