<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Rennokki\QueryCache\QueryCache;
use Rennokki\QueryCache\Test\Models\Post;

class DbCountTest extends DbTestCase
{
    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_with_count()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        /** @var CacheHit|null $hitEvent */
        $hitEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $writeEvent = $event;

            $this->assertSame([], $writeEvent->tags);
            $this->assertEquals(3600, $writeEvent->seconds);

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

        $postsCount = DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->count();

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
        $postsCountFromCache = DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->count();

        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $postsCountFromCache,
            $postsCount,
        );
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_with_count_with_columns()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        /** @var CacheHit|null $hitEvent */
        $hitEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $writeEvent = $event;

            $this->assertSame([], $writeEvent->tags);
            $this->assertEquals(3600, $writeEvent->seconds);

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

        $postsCount = DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->count(['name']);

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
        $postsCountFromCache = DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->count(['name']);

        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $postsCountFromCache,
            $postsCount,
        );
    }
}
