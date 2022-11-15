<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Event;
use Rennokki\QueryCache\Test\Models\Post;

class EloquentCountTest extends EloquentTestCase
{
    /**
     * @dataProvider eloquentContextProvider
     */
    public function test_count()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        /** @var CacheHit|null $hitEvent */
        $hitEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $writeEvent = $event;

            $this->assertSame([], $writeEvent->tags);
            $this->assertTrue(3600 >= $writeEvent->seconds);

            $this->assertStringContainsString(
                'select count(*) as aggregate from "posts"',
                $writeEvent->key,
            );
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent, &$writeEvent) {
            $hitEvent = $event;

            $this->assertSame([], $hitEvent->tags);
            $this->assertEquals($writeEvent->key, $hitEvent->key);
        });

        $posts = factory(Post::class, 5)->create();
        $postsCount = Post::cacheQuery(now()->addHours(1))->count();

        $this->assertNotNull($writeEvent);

        $this->assertEquals(
            $postsCount,
            $posts->count(),
        );

        $this->assertEquals(
            $postsCount,
            $writeEvent->value->first()->aggregate,
        );

        $this->assertEquals(
            $postsCount,
            $writeEvent->value->first()->aggregate,
        );

        // Expect a cache hit this time.
        $postsCountFromCache = Post::cacheQuery(now()->addHours(1))->count();
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $postsCountFromCache,
            $postsCount,
        );
    }

    /**
     * @dataProvider eloquentContextProvider
     */
    public function test_count_with_columns()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        /** @var CacheHit|null $hitEvent */
        $hitEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $writeEvent = $event;

            $this->assertSame([], $writeEvent->tags);
            $this->assertTrue(3600 >= $writeEvent->seconds);

            $this->assertStringContainsString(
                'select count("name") as aggregate from "posts"',
                $writeEvent->key,
            );
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent, &$writeEvent) {
            $hitEvent = $event;

            $this->assertSame([], $hitEvent->tags);
            $this->assertEquals($writeEvent->key, $hitEvent->key);
        });

        $posts = factory(Post::class, 5)->create();
        $postsCount = Post::cacheQuery(now()->addHours(1))->count(['name']);

        $this->assertNotNull($writeEvent);

        $this->assertEquals(
            $postsCount,
            $posts->count(),
        );

        $this->assertEquals(
            $postsCount,
            $writeEvent->value->first()->aggregate,
        );

        $this->assertEquals(
            $postsCount,
            $writeEvent->value->first()->aggregate,
        );

        // Expect a cache hit this time.
        $postsCountFromCache = Post::cacheQuery(now()->addHours(1))->count(['name']);
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $postsCountFromCache,
            $postsCount,
        );
    }
}