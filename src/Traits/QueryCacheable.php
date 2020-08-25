<?php

namespace Rennokki\QueryCache\Traits;

use Rennokki\QueryCache\FlushQueryCacheObserver;
use Rennokki\QueryCache\Query\Builder;

trait QueryCacheable
{
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
     * When invalidating automatically on update, you can specify
     * which tags to invalidate.
     *
     * @return array
     */
    public function getCacheTagsToInvalidateOnUpdate(): array
    {
        return $this->getCacheBaseTags();
    }

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
     * {@inheritdoc}
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        $builder = new Builder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );

        $this->cacheFor
            ? $builder->cacheFor($this->cacheFor)
            : $builder->dontCache();

        if ($this->cacheTags) {
            $builder->cacheTags($this->cacheTags);
        }

        if ($this->cachePrefix) {
            $builder->cachePrefix($this->cachePrefix);
        }

        if ($this->cacheDriver) {
            $builder->cacheDriver($this->cacheDriver);
        }

        if ($this->cacheUsePlainKey) {
            $builder->withPlainKey();
        }

        return $builder->cacheBaseTags($this->getCacheBaseTags());
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
            (string) self::class,
        ];
    }
}
