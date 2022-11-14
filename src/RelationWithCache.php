<?php

namespace Rennokki\QueryCache;

use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;

class RelationWithCache
{
    /**
     * Intercept the relation to add an eloquent builder with cache.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $builder
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public static function fromRelation(Relation $relation, $time = null)
    {
        $related = $relation->getRelated();

        if (! in_array('Rennokki\QueryCache\Traits\QueryCacheable', class_uses_recursive($related))) {
            throw new Exception(sprintf('Class %s does not use the QueryCacheable trait.', get_class($related)));
        }

        $relation->setQuery(
            EloquentBuilderWithCache::fromEloquentBuilder(
                $relation->getQuery(),
                $time,
            ),
        );

        return $relation;
    }
}
