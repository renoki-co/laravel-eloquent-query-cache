<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase as Orchestra;
use Rennokki\QueryCache\QueryCache;

abstract class DbTestCase extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        if ($this->getProvidedData()) {
            [$previousCacheKeyGeneration] = $this->getProvidedData();

            QueryCache::cacheUsePreviousKeyGenerationMethod(
                $previousCacheKeyGeneration
            );
        }
    }

    public function databaseContextProvider(): iterable
    {
        yield [true];
        yield [false];
    }
}
