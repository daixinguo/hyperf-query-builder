# Hyperf Query Builder

基于 API 请求构建 Hyperf 数据库查询。这个包允许你基于请求过滤、排序和包含 Eloquent 关联。`QueryBuilder`继承了 Hyperf 的默认 Eloquent 构建器，这意味着你所有喜欢的方法和宏仍然可用。查询参数名称尽可能地遵循[JSON API 规范](http://jsonapi.org/)。

## 目录

- [安装](#安装)
- [配置](#配置)
- [基本用法](#基本用法)
- [过滤](#过滤)
  - [基本过滤](#基本过滤)
  - [精确过滤](#精确过滤)
  - [部分匹配过滤](#部分匹配过滤)
  - [关联表过滤](#关联表过滤)
  - [范围过滤](#范围过滤)
  - [自定义过滤器](#自定义过滤器)
  - [软删除过滤](#软删除过滤)
  - [默认过滤器](#默认过滤器)
  - [忽略特定值](#忽略特定值)
- [排序](#排序)
  - [基本排序](#基本排序)
  - [默认排序](#默认排序)
  - [自定义排序](#自定义排序)
- [包含关联](#包含关联)
  - [基本关联](#基本关联)
  - [嵌套关联](#嵌套关联)
  - [关联计数](#关联计数)
  - [自定义关联](#自定义关联)
- [选择字段](#选择字段)
- [追加属性](#追加属性)
- [分页](#分页)
- [高级用法](#高级用法)
  - [与现有查询结合](#与现有查询结合)
  - [条件查询](#条件查询)
  - [异常处理](#异常处理)
- [最佳实践](#最佳实践)
- [致谢](#致谢)

## 安装

你可以通过 composer 安装此包：

```bash
composer require apielf/hyperf-query-builder
```

发布配置文件：

```bash
php bin/hyperf.php vendor:publish apielf/hyperf-query-builder
```

## 配置

配置文件位于`config/autoload/query-builder.php`，你可以在其中自定义查询参数名称：

```php
return [
    /*
     * 默认查询参数名称
     */
    'parameters' => [
        'include' => 'include',
        'filter' => 'filter',
        'sort' => 'sort',
        'fields' => 'fields',
        'append' => 'append',
    ],

    /*
     * 是否禁用无效过滤器查询异常
     */
    'disable_invalid_filter_query_exception' => false,
];
```

## 基本用法

### 基于请求过滤查询：`/users?filter[name]=John`

```php
use ApiElf\QueryBuilder\QueryBuilder;

$users = QueryBuilder::for(User::class)
    ->allowedFilters('name')
    ->get();

// 所有名称中包含"John"的`User`
```

### 基于请求包含关联：`/users?include=posts`

```php
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts')
    ->get();

// 所有`User`及其`posts`关联
```

### 基于请求排序查询：`/users?sort=id`

```php
$users = QueryBuilder::for(User::class)
    ->allowedSorts('id')
    ->get();

// 所有`User`按id升序排序
```

### 选择查询字段：`/users?fields[users]=id,email`

```php
$users = QueryBuilder::for(User::class)
    ->allowedFields(['id', 'email'])
    ->get();

// 获取的`User`只会有id和email字段
```

## 过滤

### 基本过滤

默认情况下，`filter`参数将使用部分匹配（LIKE 查询）进行过滤：

```php
// GET /users?filter[name]=John
$users = QueryBuilder::for(User::class)
    ->allowedFilters('name')
    ->get();

// 等同于 $query->where('name', 'LIKE', '%John%')
```

你可以指定多个允许的过滤器：

```php
$users = QueryBuilder::for(User::class)
    ->allowedFilters('name', 'email', 'age')
    ->get();
```

### 精确过滤

如果你需要精确匹配而不是部分匹配，可以使用`exact`过滤器：

```php
use ApiElf\QueryBuilder\AllowedFilter;

// GET /users?filter[email]=john@example.com
$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::exact('email'))
    ->get();

// 等同于 $query->where('email', 'john@example.com')
```

你也可以为过滤器指定一个内部名称，这在字段名称与过滤器名称不同时很有用：

```php
// GET /users?filter[email_address]=john@example.com
$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::exact('email_address', 'email'))
    ->get();

// 等同于 $query->where('email', 'john@example.com')
```

### 部分匹配过滤

虽然默认过滤器已经是部分匹配，但你也可以显式使用`partial`过滤器：

```php
// GET /users?filter[name]=John
$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::partial('name'))
    ->get();

// 等同于 $query->where('name', 'LIKE', '%John%')
```

### 关联表过滤

你可以使用点符号语法过滤关联表中的属性：

```php
// 假设 User 模型有一个 posts 关联
// GET /users?filter[posts.title]=Hyperf
$users = QueryBuilder::for(User::class)
    ->allowedFilters('posts.title')
    ->get();

// 等同于
// $query->whereHas('posts', function ($query) {
//     $query->where('title', 'LIKE', '%Hyperf%');
// })
```

你也可以使用精确匹配过滤器：

```php
// GET /users?filter[posts.id]=1
$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::exact('posts.id'))
    ->get();
```

关联过滤功能支持多层嵌套关联：

```php
// GET /users?filter[posts.comments.author.name]=John
$users = QueryBuilder::for(User::class)
    ->allowedFilters('posts.comments.author.name')
    ->get();
```

如果你不想允许关联过滤，可以在创建过滤器时禁用它：

```php
$users = QueryBuilder::for(User::class)
    ->allowedFilters(
        AllowedFilter::exact('name', null, null, false, false)
    )
    ->get();
```

### 范围过滤

你可以使用模型上定义的查询范围进行过滤：

```php
// 在User模型中定义范围
public function scopePopular($query)
{
    return $query->where('votes', '>', 100);
}

// GET /users?filter[popular]=true
$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::scope('popular'))
    ->get();
```

### 自定义过滤器

你可以使用回调函数创建完全自定义的过滤器：

```php
// GET /users?filter[name]=John
$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::callback('name', function ($query, $value) {
        $query->where('first_name', 'LIKE', "%{$value}%")
              ->orWhere('last_name', 'LIKE', "%{$value}%");
    }))
    ->get();
```

### 软删除过滤

如果你的模型使用了软删除，你可以使用`trashed`过滤器：

```php
// GET /users?filter[trashed]=with
$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::trashed())
    ->get();

// 可能的值:
// 'with': withTrashed()
// 'only': onlyTrashed()
// 'without': withoutTrashed()
```

### 默认过滤器

你可以为过滤器设置默认值，当请求中没有提供该过滤器时使用：

```php
// 当请求中没有filter[name]参数时，默认过滤name为'John'
$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::partial('name')->default('John'))
    ->get();
```

### 忽略特定值

你可以配置过滤器忽略特定值：

```php
// 忽略空字符串和null值
$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::exact('name')->ignore(['', null]))
    ->get();
```

## 排序

### 基本排序

你可以使用`sort`参数对结果进行排序：

```php
// GET /users?sort=name
$users = QueryBuilder::for(User::class)
    ->allowedSorts('name')
    ->get();

// 升序排序

// GET /users?sort=-name
$users = QueryBuilder::for(User::class)
    ->allowedSorts('name')
    ->get();

// 降序排序（注意前缀'-'）
```

你可以指定多个允许的排序字段：

```php
$users = QueryBuilder::for(User::class)
    ->allowedSorts('name', 'email', 'created_at')
    ->get();
```

### 默认排序

当请求中没有指定排序参数时，你可以设置默认排序：

```php
// 默认按创建时间降序排序
$users = QueryBuilder::for(User::class)
    ->allowedSorts('name', 'email', 'created_at')
    ->defaultSort('-created_at')
    ->get();
```

你也可以使用`AllowedSort`对象设置默认排序：

```php
use ApiElf\QueryBuilder\AllowedSort;

// 默认按创建时间降序排序
$users = QueryBuilder::for(User::class)
    ->allowedSorts('name', 'email', 'created_at')
    ->defaultSort(
        AllowedSort::field('created_at')
            ->defaultDirection(AllowedSort::DESCENDING)
    )
    ->get();
```

你可以设置多个默认排序：

```php
// 默认先按状态升序，再按创建时间降序排序
$users = QueryBuilder::for(User::class)
    ->allowedSorts('name', 'email', 'status', 'created_at')
    ->defaultSorts('status', '-created_at')
    ->get();
```

### 自定义排序

你可以使用自定义排序逻辑：

```php
use ApiElf\QueryBuilder\AllowedSort;
use ApiElf\QueryBuilder\Sorts\Sort;

class RandomSort implements Sort
{
    public function __invoke(QueryBuilder $query, bool $descending, string $property)
    {
        $query->inRandomOrder();
    }
}

// GET /users?sort=random
$users = QueryBuilder::for(User::class)
    ->allowedSorts(AllowedSort::custom('random', new RandomSort()))
    ->get();
```

你也可以使用回调函数创建自定义排序：

```php
use ApiElf\QueryBuilder\AllowedSort;

// GET /users?sort=full_name
$users = QueryBuilder::for(User::class)
    ->allowedSorts(
        AllowedSort::callback('full_name', function ($query, $descending, $property) {
            $direction = $descending ? 'desc' : 'asc';
            $query->orderBy('first_name', $direction)
                  ->orderBy('last_name', $direction);
        })
    )
    ->get();
```

## 包含关联

### 基本关联

你可以使用`include`参数加载关联：

```php
// GET /users?include=posts
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts')
    ->get();

// 等同于 $query->with('posts')
```

你可以指定多个允许的关联：

```php
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts', 'permissions', 'roles')
    ->get();
```

### 嵌套关联

你可以使用点符号包含嵌套关联：

```php
// GET /users?include=posts.comments
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts.comments')
    ->get();

// 等同于 $query->with('posts.comments')
```

### 关联计数

你可以使用`count`方法包含关联计数：

```php
use ApiElf\QueryBuilder\AllowedInclude;

// GET /users?include=posts_count
$users = QueryBuilder::for(User::class)
    ->allowedIncludes(AllowedInclude::count('posts_count', 'posts'))
    ->get();

// 等同于 $query->withCount('posts')
```

### 自定义关联

你可以创建自定义的关联包含逻辑：

```php
use ApiElf\QueryBuilder\AllowedInclude;
use ApiElf\QueryBuilder\Includes\IncludeInterface;

class LatestPostsInclude implements IncludeInterface
{
    public function __invoke(QueryBuilder $query)
    {
        $query->with(['posts' => function ($query) {
            $query->latest()->limit(5);
        }]);
    }
}

// GET /users?include=latest_posts
$users = QueryBuilder::for(User::class)
    ->allowedIncludes(AllowedInclude::custom('latest_posts', new LatestPostsInclude()))
    ->get();
```

## 选择字段

你可以使用`fields`参数选择要返回的字段：

```php
// GET /users?fields[users]=id,name,email
$users = QueryBuilder::for(User::class)
    ->allowedFields(['id', 'name', 'email'])
    ->get();

// 只返回id、name和email字段
```

你也可以为关联选择字段：

```php
// GET /users?fields[users]=id,name&fields[posts]=title,content
$users = QueryBuilder::for(User::class)
    ->allowedFields(['id', 'name', 'posts.title', 'posts.content'])
    ->allowedIncludes('posts')
    ->get();
```

## 追加属性

你可以使用`append`参数追加模型访问器：

```php
// 在User模型中定义访问器
public function getFullNameAttribute()
{
    return $this->first_name . ' ' . $this->last_name;
}

// GET /users?append=full_name
$users = QueryBuilder::for(User::class)
    ->allowedAppends('full_name')
    ->get();
```

## 分页

QueryBuilder 与 Hyperf 的分页功能完全兼容：

```php
// GET /users?page=2&page_size=10
$users = QueryBuilder::for(User::class)
    ->paginate($request->input('page_size', 15));

// 或者使用简单分页
$users = QueryBuilder::for(User::class)
    ->simplePaginate($request->input('page_size', 15));
```

## 高级用法

### 与现有查询结合

你可以将 QueryBuilder 与现有查询结合使用：

```php
$query = User::where('active', true);

$users = QueryBuilder::for($query) // 从现有的Builder实例开始
    ->withTrashed() // 使用你现有的作用域
    ->allowedIncludes('posts', 'permissions')
    ->where('score', '>', 42) // 链接任何Hyperf查询构建器方法
    ->get();
```

### 条件查询

你可以根据条件添加过滤器、排序和包含：

```php
$users = QueryBuilder::for(User::class)
    ->when($request->has('filter_active'), function ($query) {
        $query->allowedFilters('active');
    })
    ->when($request->has('include_posts'), function ($query) {
        $query->allowedIncludes('posts');
    })
    ->get();
```

### 异常处理

默认情况下，当请求包含未允许的过滤器、排序或包含时，QueryBuilder 会抛出异常。你可以在配置文件中禁用这些异常：

```php
// config/autoload/query-builder.php
return [
    // ...
    'disable_invalid_filter_query_exception' => true,
];
```

或者你可以在运行时禁用它们：

```php
$users = QueryBuilder::for(User::class)
    ->allowedFilters('name')
    ->allowedSorts('name')
    ->allowedIncludes('posts')
    ->disableInvalidFilterQuery()
    ->get();
```

## 最佳实践

### 在控制器中使用

```php
class UserController extends AbstractController
{
    public function index()
    {
        return QueryBuilder::for(User::class)
            ->allowedFilters(['name', 'email', AllowedFilter::exact('type')])
            ->allowedSorts(['name', 'created_at'])
            ->allowedIncludes(['posts', 'permissions'])
            ->allowedFields(['id', 'name', 'email', 'created_at'])
            ->paginate()
            ->toArray();
    }
}
```

### 创建专用的查询构建器类

对于复杂的查询，你可以创建专用的查询构建器类：

```php
class UserQueryBuilder extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct(User::query());

        $this->allowedFilters(['name', 'email', AllowedFilter::exact('type')])
            ->allowedSorts(['name', 'created_at'])
            ->allowedIncludes(['posts', 'permissions'])
            ->allowedFields(['id', 'name', 'email', 'created_at']);
    }
}

// 在控制器中使用
public function index()
{
    return (new UserQueryBuilder())->paginate();
}
```

## [所有功能未完全经过测试，如果遇到 bug 请到 github 中提交 issues](https://github.com/daixinguo/hyperf-query-builder)

## 致谢

这个包是基于[spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder)改写的，感谢原作者的出色工作。
