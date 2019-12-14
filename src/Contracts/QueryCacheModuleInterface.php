<?php

namespace Rennokki\QueryCache\Contracts;

interface QueryCacheModuleInterface
{
    /**
     * Generate the plain unique cache key for the query.
     *
     * @param  string  $method
     * @param  string|null  $id
     * @param  string|null  $appends
     * @return string
     */
    public function generatePlainCacheKey(string $method = 'get', $id = null, $appends = null): string;
}
