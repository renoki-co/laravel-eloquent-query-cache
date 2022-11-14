<?php

namespace Rennokki\QueryCache\Traits;

use Rennokki\QueryCache\FlushQueryCacheObserver;

/**
 * @method static \Rennokki\QueryCache\EloquentBuilderWithCache cacheQuery(\DateTime|int|null $time)
 * @method static \Rennokki\QueryCache\EloquentBuilderWithCache cacheFor(\DateTime|int|null $time)
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
        if (property_exists(static::class, 'flushCacheOnUpdate') && static::$flushCacheOnUpdate) {
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
    public static function getCacheBaseTags(): array
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
        /** @var \Illuminate\Database\Eloquent\Model&QueryCacheable $this */
        return $this->getCacheBaseTags();
    }

    /**
     * Flush the cache that contains specific tags.
     *
     * @param  array  $tags
     * @return bool
     */
    public static function flushQueryCache(array $tags = []): bool
    {
        return static::cacheQuery()->flushQueryCache($tags);
    }

    /**
     * Flush the cache for a specific tag.
     *
     * @param  string  $tag
     * @return bool
     */
    public static function flushQueryCacheWithTag(string $tag): bool
    {
        return static::cacheQuery()->flushQueryCacheWithTag($tag);
    }
}
