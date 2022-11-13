<?php

namespace Rennokki\QueryCache\Traits;

use Illuminate\Support\Str;
use Rennokki\QueryCache\FlushQueryCacheObserver;
use Rennokki\QueryCache\Query\Builder;

/**
 * @method static bool flushQueryCache(array $tags = [])
 * @method static bool flushQueryCacheWithTag(string $string)
 * @method static \Illuminate\Database\Query\Builder cacheQuery(\DateTime|int|null $time)
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
        /** @var \Illuminate\Database\Eloquent\Model $this */
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
        /** @var \Illuminate\Database\Eloquent\Model $this */
        return $this->getCacheBaseTags();
    }

    /**
     * Create a new query that is handled through the caching method.
     *
     * @param  \DateTime|int|null  $time
     * @return \Rennokki\QueryCache\Query\Builder
     */
    public static function cacheQuery($time = null)
    {
        /** @var \Illuminate\Database\Eloquent\Model $this */
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new static;

        $builder = new Builder(
            $model->getConnection(),
            $model->getConnection()->getQueryGrammar(),
            $model->getConnection()->getPostProcessor(),
        );

        $attributesToSeek = [
            'cacheFor',
            'cacheTags',
            'cachePrefix',
            'cacheDriver',
            'cacheUsePlainKey',
        ];

        foreach ($attributesToSeek as $attr) {
            $function = "{$attr}Value";

            if (property_exists($model, $attr)) {
                $builder->{$attr}($model->{$attr});
            }

            if (method_exists($model, $function)) {
                $builder->{$attr}(
                    $model->{$function}($builder)
                );
            }
        }

        if ($time) {
            $builder->cacheFor($time);
        }

        return $builder->cacheBaseTags($model->getCacheBaseTags());
    }
}
