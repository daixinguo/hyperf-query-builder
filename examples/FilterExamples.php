<?php

declare(strict_types=1);

namespace ApiElf\QueryBuilder\Examples;

use ApiElf\QueryBuilder\QueryBuilder;
use ApiElf\QueryBuilder\AllowedFilter;
use ApiElf\QueryBuilder\Enums\FilterOperator;

/**
 * 过滤器使用示例
 */
class FilterExamples
{
    /**
     * 基本过滤器示例
     */
    public function basicFilters()
    {
        // 精确匹配过滤器
        // GET /users?filter[email]=john@example.com
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(AllowedFilter::exact('email'))
            ->get();

        // 部分匹配过滤器（默认）
        // GET /users?filter[name]=John
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(AllowedFilter::partial('name'))
            ->get();

        // 范围过滤器
        // GET /users?filter[popular]=true
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(AllowedFilter::scope('popular'))
            ->get();
    }

    /**
     * 新增的过滤器示例
     */
    public function newFilters()
    {
        // 以指定字符串开头的严格匹配
        // GET /users?filter[name]=John
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(AllowedFilter::beginsWithStrict('name'))
            ->get();

        // 以指定字符串结尾的严格匹配
        // GET /users?filter[email]=@example.com
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(AllowedFilter::endsWithStrict('email'))
            ->get();

        // BelongsTo 关联过滤器
        // GET /posts?filter[author]=1
        $posts = QueryBuilder::for(Post::class)
            ->allowedFilters(AllowedFilter::belongsTo('author'))
            ->get();

        // 操作符过滤器
        // GET /users?filter[age]=25 (大于等于25)
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::operator('age', FilterOperator::GREATER_THAN_OR_EQUAL)
            )
            ->get();

        // GET /users?filter[score]=100 (小于100)
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::operator('score', FilterOperator::LESS_THAN)
            )
            ->get();

        // GET /users?filter[status]=active (不等于active)
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::operator('status', FilterOperator::NOT_EQUAL)
            )
            ->get();
    }

    /**
     * 关联过滤示例
     */
    public function relationFilters()
    {
        // 关联表的精确匹配
        // GET /users?filter[posts.id]=1
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(AllowedFilter::exact('posts.id'))
            ->get();

        // 关联表的部分匹配
        // GET /users?filter[posts.title]=Laravel
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(AllowedFilter::partial('posts.title'))
            ->get();

        // 关联表的操作符过滤
        // GET /users?filter[posts.views]=1000
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::operator('posts.views', FilterOperator::GREATER_THAN)
            )
            ->get();

        // 嵌套关联过滤
        // GET /users?filter[posts.comments.author.name]=John
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(AllowedFilter::partial('posts.comments.author.name'))
            ->get();
    }

    /**
     * 高级过滤示例
     */
    public function advancedFilters()
    {
        // 组合多种过滤器
        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::exact('email'),
                AllowedFilter::partial('name'),
                AllowedFilter::beginsWithStrict('username'),
                AllowedFilter::operator('age', FilterOperator::GREATER_THAN_OR_EQUAL),
                AllowedFilter::scope('active'),
                AllowedFilter::belongsTo('department'),
                'posts.title', // 默认为部分匹配
            ])
            ->get();

        // 带默认值的过滤器
        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::exact('status')->default('active'),
                AllowedFilter::operator('age', FilterOperator::GREATER_THAN_OR_EQUAL)->default(18),
            ])
            ->get();

        // 忽略特定值的过滤器
        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::exact('name')->ignore(['', null, 'all']),
                AllowedFilter::operator('score', FilterOperator::GREATER_THAN)->ignore([0, -1]),
            ])
            ->get();
    }

    /**
     * 自定义回调过滤器示例
     */
    public function customFilters()
    {
        // 自定义复杂过滤逻辑
        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where('name', 'LIKE', "%{$value}%")
                          ->orWhere('email', 'LIKE', "%{$value}%")
                          ->orWhere('username', 'LIKE', "%{$value}%");
                }),
                AllowedFilter::callback('date_range', function ($query, $value) {
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween('created_at', $value);
                    }
                }),
            ])
            ->get();
    }
}
