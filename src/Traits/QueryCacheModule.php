<?php

namespace Rennokki\QueryCache\Traits;

use BadMethodCallException;
use DateTime;

trait QueryCacheModule
{
    /**
     * The number of seconds or the DateTime instance
     * that specifies how long to cache the query.
     *
     * @var int|\DateTime
     */
    protected $cacheFor;

    /**
     * The tags for the query cache. Can be useful
     * if flushing cache for specific tags only.
     *
     * @var null|array
     */
    protected $cacheTags = null;

    /**
     * The tags for the query cache that
     * will be present on all queries.
     *
     * @var null|array
     */
    protected $cacheBaseTags = [];

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
     * need to be plain instead of being hashed.
     *
     * @var bool
     */
    protected $cacheUsePlainKey = false;

    /**
     * Specify if the cache key generation method should work like in 3.x
     *
     * @var bool
     */
    protected $cacheUsePreviousKeyGenerationMethod = false;

    /**
     * Set if the caching should be avoided.
     *
     * @var bool
     */
    protected $avoidCache = true;

    /**
     * Get the cache from the current query.
     *
     * @param  string  $method
     * @param  array  $columns
     * @param  string|null  $id
     * @return array
     */
    public function getFromQueryCache(string $method = 'get', array $columns = ['*'], string $id = null)
    {
        /** @var \Rennokki\QueryCache\QueryBuilderWithCache $this */
        $key = $this->getCacheKey($method, $columns);
        $cache = $this->getCache();
        $callback = $this->getQueryCacheCallback($method, $columns, $id);
        $time = $this->getCacheFor();

        if ($time instanceof DateTime || $time > 0) {
            return $cache->remember($key, $time, $callback);
        }

        return $cache->rememberForever($key, $callback);
    }

    /**
     * Get the query cache callback.
     *
     * @param  string  $method
     * @param  array|string  $columns
     * @param  string|null  $id
     * @return \Closure
     */
    public function getQueryCacheCallback(string $method = 'get', $columns = ['*'], string $id = null)
    {
        /** @var \Rennokki\QueryCache\QueryBuilderWithCache $this */
        return function () use ($method, $columns) {
            $this->avoidCache = true;

            return $this->{$method}($columns);
        };
    }

    /**
     * Get a unique cache key for the complete query.
     *
     * @param  string  $method
     * @param  array  $columns
     * @param  string|null  $id
     * @param  string|null  $appends
     * @return string
     */
    public function getCacheKey(string $method = 'get', array $columns = ['*'], string $id = null, string $appends = null): string
    {
        /** @var \Rennokki\QueryCache\QueryBuilderWithCache $this */
        $key = $this->generateCacheKey($method, $columns, $id, $appends);
        $prefix = $this->getCachePrefix();

        return "{$prefix}:{$key}";
    }

    /**
     * Generate the unique cache key for the query.
     *
     * @param  string  $method
     * @param  array  $columns
     * @param  string|null  $id
     * @param  string|null  $appends
     * @return string
     */
    public function generateCacheKey(string $method = 'get', array $columns = ['*'], string $id = null, string $appends = null): string
    {
        /** @var \Rennokki\QueryCache\QueryBuilderWithCache $this */
        $key = $this->generatePlainCacheKey($method, $columns, $id, $appends);

        if ($this->shouldUsePlainKey()) {
            return $key;
        }

        return hash('sha256', $key);
    }

    /**
     * Generate the plain unique cache key for the query.
     *
     * @param  string  $method
     * @param  array  $columns
     * @param  string|null  $id
     * @param  string|null  $appends
     * @return string
     */
    public function generatePlainCacheKey(string $method = 'get', array $columns = ['*'], string $id = null, string $appends = null): string
    {
        /** @var \Rennokki\QueryCache\QueryBuilderWithCache $this */
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->getConnection();
        $name = $connection->getName();

        if ($this->shouldUsePreviousKeyGenerationMethod()) {
            return $name.$method.$id.$this->toSql().serialize($this->getBindings()).$appends;
        }

        return sprintf(
            '%s;%s;%s;%s;%s;%s;%s',
            $name,
            $method,
            collect($columns)->join(':'),
            $id,
            $this->toSql(),
            collect($this->getBindings())->map(fn ($v, $k) => "{$k}:{$v}")->join(';'),
            $appends,
        );
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

        if (! $tags) {
            $tags = $this->getCacheBaseTags();
        }

        foreach ($tags as $tag) {
            $this->flushQueryCacheWithTag($tag);
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

        try {
            return $cache->tags($tag)->flush();
        } catch (BadMethodCallException $e) {
            return $cache->flush();
        }
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int|null  $time
     * @return $this
     */
    public function cacheFor($time)
    {
        $this->cacheFor = $time;
        $this->avoidCache = $time === null;

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
     * @param  bool  $avoidCache
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function dontCache(bool $avoidCache = true)
    {
        $this->avoidCache = $avoidCache;

        return $this;
    }

    /**
     * Alias for dontCache().
     *
     * @param  bool  $avoidCache
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function doNotCache(bool $avoidCache = true)
    {
        return $this->dontCache($avoidCache);
    }

    /**
     * Alias for dontCache().
     *
     * @param  bool  $avoidCache
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function avoidCache(bool $avoid = true)
    {
        return $this->dontCache($avoid);
    }

    /**
     * Set the cache prefix.
     *
     * @param  string  $prefix
     * @return $this
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
     * @return $this
     */
    public function cacheTags(array $cacheTags = [])
    {
        $this->cacheTags = $cacheTags;

        return $this;
    }

    /**
     * Append tags to the cache.
     *
     * @param  array  $cacheTags
     * @return $this
     */
    public function appendCacheTags(array $cacheTags = [])
    {
        $this->cacheTags = array_unique(array_merge($this->cacheTags ?? [], $cacheTags));

        return $this;
    }

    /**
     * Use a specific cache driver.
     *
     * @param  string  $cacheDriver
     * @return $this
     */
    public function cacheDriver(string $cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;

        return $this;
    }

    /**
     * Set the base cache tags; the tags
     * that will be present on all cached queries.
     *
     * @param  array  $tags
     * @return $this
     */
    public function cacheBaseTags(array $tags = [])
    {
        $this->cacheBaseTags = $tags;

        return $this;
    }

    /**
     * Use a plain key instead of a hashed one, in the cache driver.
     *
     * @param  bool  $usePlainKey
     * @return $this
     */
    public function withPlainKey(bool $usePlainKey = true)
    {
        $this->cacheUsePlainKey = $usePlainKey;

        return $this;
    }

    /**
     * Alias for withPlainKey().
     *
     * @param  bool  $usePlainKey
     * @return $this
     */
    public function cacheUsePlainKey(bool $usePlainKey = true)
    {
        return $this->withPlainKey($usePlainKey);
    }

    /**
     * Specify that the cache key generation method should be still used
     * as in the prev. version (3.x).
     *
     * @param  bool  $usePreviousKeyGenerationMethod
     * @return $this
     */
    public function cacheUsePreviousKeyGenerationMethod(bool $usePreviousKeyGenerationMethod = true)
    {
        $this->cacheUsePreviousKeyGenerationMethod = $usePreviousKeyGenerationMethod;

        return $this;
    }

    /**
     * Get the cache driver.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    public function getCacheDriver()
    {
        return app('cache')->driver($this->cacheDriver);
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    public function getCache()
    {
        $cache = $this->getCacheDriver();
        $tags = $this->computeTags();

        try {
            return $tags ? $cache->tags($tags) : $cache;
        } catch (BadMethodCallException $e) {
            return $cache;
        }
    }

    /**
     * Compute the final tags used for caching.
     *
     * @return array
     */
    public function computeTags(): array
    {
        return collect()
            ->merge($this->getCacheTags() ?: [])
            ->merge($this->getCacheBaseTags() ?: [])
            ->unique()
            ->all();
    }

    /**
     * Check if the cache operation should be avoided.
     *
     * @return bool
     */
    public function shouldAvoidCache(): bool
    {
        return $this->avoidCache;
    }

    /**
     * Check if the cache operation key should use a plain
     * query key.
     *
     * @return bool
     */
    public function shouldUsePlainKey(): bool
    {
        return $this->cacheUsePlainKey;
    }

    /**
     * Check if the method generation method for the cache keys
     * should be the same as in prev. version. (3.x)
     *
     * @return bool
     */
    public function shouldUsePreviousKeyGenerationMethod(): bool
    {
        return $this->cacheUsePreviousKeyGenerationMethod;
    }

    /**
     * Get the cache time attribute.
     *
     * @return int|\DateTime
     */
    public function getCacheFor()
    {
        return $this->cacheFor;
    }

    /**
     * Get the cache tags attribute.
     *
     * @return array|null
     */
    public function getCacheTags()
    {
        return $this->cacheTags;
    }

    /**
     * Get the base cache tags attribute.
     *
     * @return array|null
     */
    public function getCacheBaseTags()
    {
        return $this->cacheBaseTags;
    }

    /**
     * Get the cache prefix attribute.
     *
     * @return string
     */
    public function getCachePrefix(): string
    {
        return $this->cachePrefix;
    }
}
