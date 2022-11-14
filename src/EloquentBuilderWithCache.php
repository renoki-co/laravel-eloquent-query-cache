<?php

namespace Rennokki\QueryCache;

use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @method static \Rennokki\QueryCache\EloquentBuilderWithCache cacheQuery(\DateTime|int|null $time)
 * @method static \Rennokki\QueryCache\EloquentBuilderWithCache cacheFor(\DateTime|int|null $time)
 * @mixin \Rennokki\QueryCache\QueryBuilderWithCache
 */
class EloquentBuilderWithCache extends EloquentBuilder
{
    /**
     * The original Eloquent Builder.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected EloquentBuilder $originalEloquentBuilder;

    /**
     * Create a new EloquentBuilder with cache.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return static
     */
    public static function fromEloquentBuilder(EloquentBuilder $eloquentBuilder, $time = null)
    {
        $model = $eloquentBuilder->getModel();

        if (! in_array('Rennokki\QueryCache\Traits\QueryCacheable', class_uses_recursive($model))) {
            throw new Exception(sprintf('Class %s does not use the QueryCacheable trait.', get_class($model)));
        }

        $eloquentBuilderWithCache = new static(
           QueryBuilderWithCache::fromQueryBuilder(
                $eloquentBuilder->getQuery(),
                $time,
                $model,
            )
        );

        $eloquentBuilderWithCache->passthru = array_merge([
            'flushQueryCacheWithTag',
            'flushQueryCache',
            'getCacheFor',
            'getCachePrefix',
            'getCacheBaseTags',
            'getCacheTags',
        ], $eloquentBuilderWithCache->passthru);

        $eloquentBuilderWithCache->setOriginalEloquentBuilder($eloquentBuilder);
        $eloquentBuilderWithCache->setModel($model);

        return $eloquentBuilderWithCache;
    }

    /**
     * Set the original EloquentBuilder for reference.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return $this
     */
    public function setOriginalEloquentBuilder(EloquentBuilder $builder)
    {
        $this->originalEloquentBuilder = $builder;

        return $this;
    }

    /**
     * Get the non-cache eloquent builder.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getOriginalEloquentBuilder()
    {
        return $this->originalEloquentBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        parent::__clone();

        $this->originalEloquentBuilder = clone $this->originalEloquentBuilder;
        $this->query = clone $this->query;
    }
}
