<?php

namespace Rennokki\QueryCache;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Rennokki\QueryCache\EloquentBuilderWithCache;

class QueryCacheServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        EloquentBuilder::macro('cacheQuery', function ($time = null) {
            /** @var EloquentBuilder $this */
            return EloquentBuilderWithCache::fromEloquentBuilder($this, $time);
        });

        EloquentBuilder::macro('cacheFor', function ($time = null) {
            /** @var EloquentBuilder $this */
            return $this->cacheQuery($time);
        });

        QueryBuilder::macro('cacheQuery', function ($time = null) {
            /** @var QueryBuilder $this */
            return QueryBuilderWithCache::fromQueryBuilder($this, $time);
        });

        QueryBuilder::macro('cacheFor', function ($time = null) {
            /** @var QueryBuilder $this */
            return $this->cacheQuery($time);
        });

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

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
