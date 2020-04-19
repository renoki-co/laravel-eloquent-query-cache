<?php

namespace Rennokki\QueryCache\Traits;

use Rennokki\QueryCache\Query\Builder;

trait QueryCacheable
{
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

        if ($this->cacheFor) {
            $builder->cacheFor($this->cacheFor);
        } else {
            $builder->dontCache();
        }

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

        return $builder
            ->cacheBaseTags($this->getCacheBaseTags());
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
