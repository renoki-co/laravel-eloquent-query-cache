<?php

namespace Rennokki\QueryCache;

use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use ReflectionClass;

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
    protected EloquentBuilder $eloquentBuilder;

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

        $eloquentBuilderWithCache = new static($eloquentBuilder->getQuery());

        // Pull properties from the original class.
        $eloquentBuilderReflection = new ReflectionClass($eloquentBuilder);
        $propertiesToPull = $eloquentBuilderReflection->getProperties();

        foreach ($propertiesToPull as $property) {
            if ($property->isStatic()) {
                // TODO: Set static::{$property->name} = $builder::{$property->name};
                continue;
            }

            $eloquentBuilderWithCache->{$property->name} = $eloquentBuilder->{$property->name};
        }

        // Update the passthru to also include the underlying QueryBuilderWithCache builder.
        $eloquentBuilderWithCache->passthru = array_merge([
            'flushQueryCacheWithTag',
            'flushQueryCache',
            'getCacheFor',
            'getCachePrefix',
            'getCacheBaseTags',
            'getCacheTags',
        ], $eloquentBuilderWithCache->passthru);

        // Update the underlying query to use cache.
        $eloquentBuilderWithCache->setQuery(
            QueryBuilderWithCache::fromQueryBuilder(
                $eloquentBuilderWithCache->getQuery(),
                $time,
                $model,
            )
        );

        $eloquentBuilderWithCache->setEloquentBuilder($eloquentBuilder);
        $eloquentBuilderWithCache->setModel($model);

        return $eloquentBuilderWithCache;
    }

    /**
     * Set the original EloquentBuilder for reference.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return $this
     */
    public function setEloquentBuilder(EloquentBuilder $builder)
    {
        $this->eloquentBuilder = $builder;

        return $this;
    }

    /**
     * Get the non-cache eloquent builder.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getEloquentBuilder()
    {
        return $this->eloquentBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        parent::__clone();

        $this->eloquentBuilder = clone $this->eloquentBuilder;
        $this->query = clone $this->query;
    }
}
