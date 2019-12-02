<?php

namespace Rennokki\QueryCache\Query;

use DateTime;
use Illuminate\Database\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * The number of seconds or the DateTime instance
     * that specifies how long to cache the query.
     *
     * @var int|\DateTime
     */
    protected $cacheTime;

    /**
     * The tags for the query cache. Can be useful
     * if flushing cache for specific tags only.
     *
     * @var null|array
     */
    protected $cacheTags = null;

    /**
     * The cache driver to be used.
     *
     * @var string
     */
    protected $cacheDriver;

    /**
     * A cache prefix string that will be prefixed
     * on each cache key generation.
     *
     * @var string
     */
    protected $cachePrefix = 'leqc';

    /**
     * Specify if the key that should be used when caching the query
     * need to be plain or be hashed.
     *
     * @var bool
     */
    protected $cacheUsePlainKey = false;

    /**
     * Set if the caching should be avoided.
     *
     * @var bool
     */
    protected $avoidCache = true;

    /**
     * {@inheritdoc}
     */
    public function get($columns = ['*'])
    {
        if (! $this->avoidCache) {
            return $this->getFromQueryCache('get', $columns);
        }

        return parent::get($columns);
    }

    /**
     * Get the cache from the current query.
     *
     * @param array $columns
     * @return array
     */
    protected function getFromQueryCache(string $method = 'get', $columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        $key = $this->getCacheKey('get');
        $cache = $this->getCache();
        $callback = $this->getQueryCacheCallback($method, $columns);

        if ($this->cacheTime instanceof DateTime || $this->cacheTime > 0) {
            return $cache->remember($key, $this->cacheTime, $callback);
        }

        return $cache->rememberForever($key, $callback);
    }

    /**
     * Get the query cache callback.
     *
     * @param  string  $method
     * @param  array  $columns
     * @return \Closure
     */
    protected function getQueryCacheCallback(string $method = 'get', $columns = ['*'])
    {
        return function () use ($method, $columns) {
            $this->avoidCache = true;

            return $this->{$method}($columns);
        };
    }

    /**
     * Get a unique cache key for the complete query.
     *
     * @param  string  $method
     * @param  string|null  $id
     * @param  string|null  $appends
     * @return string
     */
    public function getCacheKey(string $method = 'get', $id = null, $appends = null): string
    {
        $key = $this->generateCacheKey($method, $id, $appends);

        return "{$this->cachePrefix}:{$key}";
    }

    /**
     * Generate the unique cache key for the query.
     *
     * @param  string  $method
     * @param  string|null  $id
     * @param  string|null  $appends
     * @return string
     */
    public function generateCacheKey(string $method = 'get', $id = null, $appends = null): string
    {
        $key = $this->generatePlainCacheKey($method, $id, $appends);

        if ($this->cacheUsePlainKey) {
            return $key;
        }

        return hash('sha256', $key);
    }

    /**
     * Generate the plain unique cache key for the query.
     *
     * @param  string  $method
     * @param  string|null  $id
     * @param  string|null  $appends
     * @return string
     */
    public function generatePlainCacheKey(string $method = 'get', $id = null, $appends = null): string
    {
        $name = $this->connection->getName();

        // Count has no Sql, that's why it can't be used ->toSql()
        if ($method === 'count') {
            return $name.$method.$id.serialize($this->getBindings()).$appends;
        }

        return $name.$method.$id.$this->toSql().serialize($this->getBindings()).$appends;
    }

    /**
     * Flush the cache that contains specific tags.
     *
     * @param  array  $tags
     * @return bool
     */
    public function flushQueryCache(array $tags = []): bool
    {
        $cache = $this->getCacheDriver();

        if (! method_exists($cache, 'tags')) {
            return false;
        }

        foreach ($tags as $tag) {
            self::flushQueryCacheWithTag($tag);
        }

        return true;
    }

    /**
     * Flush the cache for a specific tag.
     *
     * @param  string  $tag
     * @return bool
     */
    public function flushQueryCacheWithTag(string $tag): bool
    {
        $cache = $this->getCacheDriver();

        if (! method_exists($cache, 'tags')) {
            return false;
        }

        return $cache->tags($tag)->flush();
    }

    /**
     * Get the cache driver.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCacheDriver()
    {
        return app('cache')->driver($this->cacheDriver);
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCache()
    {
        $cache = $this->getCacheDriver();

        return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache;
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int  $time
     * @return \Rennokki\QueryCache\Query\Builder
     */
    public function cacheFor($time)
    {
        $this->cacheTime = $time;

        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function cacheForever()
    {
        return $this->cacheFor(-1);
    }

    /**
     * Indicate that the query should not be cached.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function dontCache()
    {
        $this->avoidCache = true;

        return $this;
    }

    /**
     * Alias for dontCache().
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function doNotCache()
    {
        return $this->dontCache();
    }

    /**
     * Set the cache prefix.
     *
     * @param  string  $prefix
     * @return \Rennokki\QueryCache\Query\Builder
     */
    public function cachePrefix(string $prefix)
    {
        $this->cachePrefix = $prefix;

        return $this;
    }

    /**
     * Attach tags to the cache.
     *
     * @param  array  $cacheTags
     * @return \Rennokki\QueryCache\Query\Builder
     */
    public function cacheTags(array $cacheTags = [])
    {
        $this->cacheTags = $cacheTags;

        return $this;
    }

    /**
     * Use a specific cache driver.
     *
     * @param  string  $cacheDriver
     * @return \Rennokki\QueryCache\Query\Builder
     */
    public function cacheDriver(string $cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;

        return $this;
    }

    /**
     * Use a plain key instead of a hashed one in the cache driver.
     *
     * @return \Rennokki\QueryCache\Query\Builder
     */
    public function withPlainKey()
    {
        $this->cacheUsePlainKey = true;

        return $this;
    }
}
