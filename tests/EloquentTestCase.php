<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\QueryCache;

abstract class EloquentTestCase extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        if ($this->getProvidedData()) {
            [$strict, $previousCacheKeyGeneration] = $this->getProvidedData();

            if (method_exists(Model::class, 'preventAccessingMissingAttributes')) {
                Model::preventAccessingMissingAttributes($strict);
            }

            QueryCache::cacheUsePreviousKeyGenerationMethod(
                $previousCacheKeyGeneration
            );
        }
    }

    public function eloquentContextProvider(): iterable
    {
        return collect([true, false]) // Strict mode for models
            ->crossJoin([true, false]) // cacheUsePreviousKeyGenerationMethod()
            ->all();
    }
}
