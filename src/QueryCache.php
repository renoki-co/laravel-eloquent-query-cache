<?php

namespace Rennokki\QueryCache;

/**
 * @method static void cacheFor(\DateTime|int|null $time) Set the number of seconds or the DateTime instance that specifies how long to cache the query.
 * @method static void cacheTags(array $tags) Set the tags for the query cache. Can be useful if flushing cache for specific tags only.
 * @method static void cacheBaseTags(array $tags) Set the tags for the query cache that will be present on all queries.
 * @method static void cacheDriver(string $driver) Set the cache driver to be used.
 * @method static void cachePrefix(string $prefix) Set the cache prefix string that will be prefixed on each cache key generation.
 * @method static void cacheUsePlainKey(bool $usePlainKey) Specify if the key that should be used when caching the query need to be plain or be hashed.
 * @method static void cacheUsePreviousKeyGenerationMethod(bool $usePreviousGenerationMethod) Specify if the cache key generation should work like in 3.x
 * @method static void avoidCache(bool $avoid) Specify if the caching should be avoided.
 */
class QueryCache
{
    /**
     * The list of options for global query caching.
     *
     * @var array
     */
    public static $options = [
        'cacheFor' => null,
        'cacheTags' => null,
        'cacheBaseTags' => null,
        'cacheDriver' => null,
        'cachePrefix' => null,
        'cacheUsePlainKey' => null,
        'cacheUsePreviousKeyGenerationMethod' => null,
        'avoidCache' => null,
    ];

    /**
     * Reset the properties.
     *
     * @return void
     */
    public static function reset(): void
    {
        foreach (static::$options as $key => $value) {
            static::$options[$key] = null;
        }
    }

    /**
     * Get a specific option value.
     *
     * @param  string  $key
     * @return mixed
     */
    public static function getOption(string $name)
    {
        return static::$options[$name] ?? null;
    }

    /**
     * Proxy the call to the local class.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return void
     */
    public static function __callStatic($name, $arguments): void
    {
        static::$options[$name] = $arguments[0];
    }
}
