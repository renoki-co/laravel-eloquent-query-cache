<?php

namespace Rennokki\QueryCache;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\ServiceProvider;

class QueryCacheServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootConnectionMacros();
        $this->bootEloquentQueryBuilderMacros();
        $this->bootQueryBuilderMacros();
        $this->bootRelationMacros();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the connection macros.
     *
     * @return void
     */
    protected function bootConnectionMacros(): void
    {
        Connection::macro('flushQueryCache', function (array $tags = []) {
            /** @var Connection $this */
            return $this->query()
                ->cacheQuery()
                ->flushQueryCache($tags);
        });
    }

    /**
     * Boot the eloquent query builder macros.
     *
     * @return void
     */
    protected function bootEloquentQueryBuilderMacros(): void
    {
        EloquentBuilder::macro('cacheQuery', function ($time = null) {
            /** @var EloquentBuilder $this */
            return EloquentBuilderWithCache::fromEloquentBuilder($this, $time);
        });

        EloquentBuilder::macro('cacheFor', function ($time = null) {
            /** @var EloquentBuilder $this */
            return $this->cacheQuery($time);
        });
    }

    /**
     * Boot the query builder macros.
     *
     * @return void
     */
    protected function bootQueryBuilderMacros(): void
    {
        QueryBuilder::macro('cacheQuery', function ($time = null) {
            /** @var QueryBuilder $this */
            return QueryBuilderWithCache::fromQueryBuilder($this, $time);
        });

        QueryBuilder::macro('cacheFor', function ($time = null) {
            /** @var QueryBuilder $this */
            return $this->cacheQuery($time);
        });
    }

    /**
     * Boot the relation macros.
     *
     * @return void
     */
    protected function bootRelationMacros(): void
    {
        Relation::macro('cacheQuery', function ($time = null) {
            /** @var Relation $this */
            return RelationWithCache::fromRelation($this, $time);
        });

        Relation::macro('cacheFor', function ($time = null) {
            /** @var Relation $this */
            return $this->cacheQuery($time);
        });

        Relation::macro('setQuery', function (EloquentBuilder $builder) {
            /** @var Relation $this */
            $this->query = $builder;
        });
    }
}
