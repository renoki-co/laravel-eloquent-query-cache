<?php

namespace Rennokki\QueryCache;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use ReflectionClass;
use Rennokki\QueryCache\Contracts\QueryCacheModuleInterface;
use Rennokki\QueryCache\Traits\QueryCacheModule;

/**
 * @method static \Rennokki\QueryCache\QueryBuilderWithCache cacheQuery(\DateTime|int|null $time)
 * @method static \Rennokki\QueryCache\QueryBuilderWithCache cacheFor(\DateTime|int|null $time)
 */
class QueryBuilderWithCache extends QueryBuilder implements QueryCacheModuleInterface
{
    use QueryCacheModule;

    /**
     * The original Query Builder.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected QueryBuilder $queryBuilder;

    /**
     * Create a new QueryBuilder with cache.
     *
     * @param  \Illuminate\Database\Query\Builder  $builder
     * @param  \DateTime|int|null  $time
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return static
     */
    public static function fromQueryBuilder(QueryBuilder $builder, $time = null, Model $model = null)
    {
        $builderWithCache = new static(
            $builder->getConnection(),
            $builder->getGrammar(),
            $builder->getProcessor(),
        );

        // Pull properties from the original class.
        $builderReflection = new ReflectionClass($builder);
        $propertiesToPull = $builderReflection->getProperties();

        foreach ($propertiesToPull as $property) {
            if ($property->isStatic()) {
                // dump($property);
                // $name = $property->name;
                // TODO: static::${$name} = $builder::${$name};
                continue;
            }

            $builderWithCache->{$property->name} = $builder->{$property->name};
        }

        $builderWithCache->setQueryBuilder($builder);

        // These are the names for custom model properties, model methods
        // and global static properties.
        $attributesToSeek = [
            'cacheFor',
            'cacheTags',
            'cachePrefix',
            'cacheDriver',
            'cacheUsePlainKey',
            'cacheUsePreviousKeyGenerationMethod',
            'avoidCache',
        ];

        // For raw query builder, we also need to seek for cacheBaseTags,
        // if declared globally through QueryCache::cacheBaseTags([])
        if (! $model) {
            $attributesToSeek[] = 'cacheBaseTags';
        }

        foreach ($attributesToSeek as $attr) {
            if ($model) {
                // When used with underlying Eloquent, seek within the model for variables
                // that build the values for the cache module.
                $function = "{$attr}Value";

                if (property_exists($model, $attr)) {
                    $builderWithCache->{$attr}($model->{$attr});
                }

                if (method_exists($model, $function)) {
                    $builderWithCache->{$attr}(
                        $model->{$function}($builderWithCache)
                    );
                }
            }

            // When global attributes are being set, write them to the builder.
            if (! is_null($value = QueryCache::getOption($attr))) {
                $builderWithCache->{$attr}($value);
            }
        }

        if ($time) {
            $builderWithCache->cacheFor($time);
        }

        return $model
            ? $builderWithCache->cacheBaseTags($model->getCacheBaseTags())
            : $builderWithCache;
    }

    /**
     * Set the original QueryBuilder for reference.
     *
     * @param  \Illuminate\Database\Query\Builder  $builder
     * @return $this
     */
    public function setQueryBuilder(QueryBuilder $builder)
    {
        $this->queryBuilder = $builder;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($columns = ['*'])
    {
        return $this->shouldAvoidCache()
            ? parent::get($columns)
            : $this->getFromQueryCache('get', Arr::wrap($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function useWritePdo()
    {
        // Do not cache when using the write pdo for query.
        $this->dontCache();

        // Call parent method
        parent::useWritePdo();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function selectSub($query, $as)
    {
        if (! is_string($query) && get_class($query) == self::class) {
            $this->appendCacheTags($query->getCacheTags() ?? []);
        }

        return parent::selectSub($query, $as);
    }

    /**
     * Get the original query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function clone()
    {
        $clone = parent::clone();

        $clone->setQueryBuilder(clone $this->queryBuilder);

        return $clone;
    }
}
