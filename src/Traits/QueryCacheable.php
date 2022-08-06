<?php

namespace Rennokki\QueryCache\Traits;

use Illuminate\Support\Str;
use Rennokki\QueryCache\FlushQueryCacheObserver;
use Rennokki\QueryCache\Query\Builder;

/**
 * @method static bool flushQueryCache(array $tags = [])
 * @method static bool flushQueryCacheWithTag(string $string)
 * @method static \Illuminate\Database\Query\Builder|static cacheFor(\DateTime|int|null $time)
 * @method static \Illuminate\Database\Query\Builder|static cacheForever()
 * @method static \Illuminate\Database\Query\Builder|static dontCache()
 * @method static \Illuminate\Database\Query\Builder|static doNotCache()
 * @method static \Illuminate\Database\Query\Builder|static cachePrefix(string $prefix)
 * @method static \Illuminate\Database\Query\Builder|static cacheTags(array $cacheTags = [])
 * @method static \Illuminate\Database\Query\Builder|static appendCacheTags(array $cacheTags = [])
 * @method static \Illuminate\Database\Query\Builder|static cacheDriver(string $cacheDriver)
 * @method static \Illuminate\Database\Query\Builder|static cacheBaseTags(array $tags = [])
 */
trait QueryCacheable
{
    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootQueryCacheable()
    {
        if (isset(static::$flushCacheOnUpdate) && static::$flushCacheOnUpdate) {
            static::observe(
                static::getFlushQueryCacheObserver()
            );
        }
    }

    /**
     * Get the observer class name that will
     * observe the changes and will invalidate the cache
     * upon database change.
     *
     * @return string
     */
    protected static function getFlushQueryCacheObserver()
    {
        return FlushQueryCacheObserver::class;
    }

    /**
     * Set the base cache tags that will be present
     * on all queries.
     *
     * @return array
     */
    protected function getCacheBaseTags(): array
    {
        return [
            (string) static::class,
        ];
    }

    /**
     * When invalidating automatically on update, you can specify
     * which tags to invalidate.
     *
     * @param  string|null  $relation
     * @param  \Illuminate\Database\Eloquent\Collection|null  $pivotedModels
     * @return array
     */
    public function getCacheTagsToInvalidateOnUpdate($relation = null, $pivotedModels = null): array
    {
        return $this->getCacheBaseTags();
    }

    /**
     * Create a new query that is handled through the caching method.
     *
     * @param  \DateTime|int|null  $time
     * @return \Rennokki\QueryCache\Query\Builder
     */
    protected static function cacheQuery($time)
    {
        $model = (new static);

        $connection = $model->getConnection();

        $builder = new Builder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );

        $builder->cacheFor($time);

        if ($model->cacheFor) {
            $builder->cacheFor($model->cacheFor);
        }

        if ($model->cacheTags) {
            $builder->cacheTags($model->cacheTags);
        }

        if ($model->cachePrefix) {
            $builder->cachePrefix($model->cachePrefix);
        }

        if ($model->cacheDriver) {
            $builder->cacheDriver($model->cacheDriver);
        }

        if ($model->cacheUsePlainKey) {
            $builder->withPlainKey();
        }

        $methodsToSeek = [
            'cacheForValue',
            'cacheTagsValue',
            'cachePrefixValue',
            'cacheDriverValue',
            'cacheUsePlainKeyValue',
        ];

        foreach ($methodsToSeek as $method) {
            if (method_exists($model, $method)) {
                $builder->{Str::before($method, 'Value')}(
                    $model->{$method}($builder)
                );
            }
        }

        return $builder->cacheBaseTags($model->getCacheBaseTags());
    }
}
