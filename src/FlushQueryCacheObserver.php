<?php

namespace Rennokki\QueryCache;

use Exception;
use Illuminate\Database\Eloquent\Model;

class FlushQueryCacheObserver
{
    /**
     * Handle the Model "created" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function created(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "updated" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function updated(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "deleted" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function deleted(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "forceDeleted" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function forceDeleted(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "restored" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function restored(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Invalidate the cache for a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     * @throws Exception
     */
    protected function invalidateCache(Model $model): void
    {
        $class = get_class($model);

        if (! $model->getCacheTagsToInvalidateOnUpdate()) {
            throw new Exception('Automatic invalidation for '.$class.' works only if at least one tag to be invalidated is specified.');
        }

        $class::flushQueryCache(
            $model->getCacheTagsToInvalidateOnUpdate()
        );
    }
}
