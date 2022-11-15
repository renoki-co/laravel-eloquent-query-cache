<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Rennokki\QueryCache\Test\Models\Post;

class DbFirstTest extends DbTestCase
{
    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_first()
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
                'select * from "posts" limit 1',
                $writeEvent->key,
            );
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent, &$writeEvent) {
            $hitEvent = $event;

            $this->assertSame([], $hitEvent->tags);
            $this->assertEquals($writeEvent->key, $hitEvent->key);
        });

        $posts = factory(Post::class, 5)->create();

        $storedPost = DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->first();

        $this->assertNotNull($writeEvent);

        $this->assertEquals(
            $storedPost->id,
            $posts->first()->id,
        );

        $this->assertEquals(
            $storedPost->id,
            $writeEvent->value->first()->id,
        );

        $this->assertEquals(
            $storedPost->id,
            $writeEvent->value->first()->id,
        );

        // Expect a cache hit this time.
        $storedPostFromCache = DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->first();

        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostFromCache->id,
            $storedPost->id,
        );
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_first_with_columns()
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
                'select * from "posts" limit 1',
                $writeEvent->key,
            );
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent, &$writeEvent) {
            $hitEvent = $event;

            $this->assertSame([], $hitEvent->tags);
            $this->assertEquals($writeEvent->key, $hitEvent->key);
        });

        $posts = factory(Post::class, 5)->create();

        $storedPost = DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->first(['name']);

        $this->assertNotNull($writeEvent);

        $this->assertEquals(
            $storedPost->name,
            $posts->first()->name,
        );

        $this->assertEquals(
            $storedPost->name,
            $writeEvent->value->first()->name,
        );

        $this->assertEquals(
            $storedPost->name,
            $writeEvent->value->first()->name,
        );

        // Expect a cache hit this time.
        $storedPostFromCache = DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->first(['name']);

        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostFromCache->name,
            $storedPost->name,
        );
    }
}
