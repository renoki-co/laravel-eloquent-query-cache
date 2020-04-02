<?php

namespace Rennokki\QueryCache\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Rennokki\QueryCache\Contracts\QueryCacheModuleInterface;
use Rennokki\QueryCache\Traits\QueryCacheModule;

class Builder extends BaseBuilder implements QueryCacheModuleInterface
{
    use QueryCacheModule;

    /**
     * {@inheritdoc}
     */
    public function get($columns = ['*'])
    {
        if (! $this->shouldAvoidCache()) {
            return $this->getFromQueryCache('get', $columns);
        }

        return parent::get($columns);
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
}
