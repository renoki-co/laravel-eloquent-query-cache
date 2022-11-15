<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Rennokki\QueryCache\Test\Models\Post;

class DbGetTest extends DbTestCase
{
    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_get()
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
                'select * from "posts"',
                $writeEvent->key,
            );
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent, &$writeEvent) {
            $hitEvent = $event;

            $this->assertSame([], $hitEvent->tags);
            $this->assertEquals($writeEvent->key, $hitEvent->key);
        });

        $posts = factory(Post::class, 5)->create();
        $storedPosts = DB::table('posts')->cacheQuery(now()->addHours(1))->get();

        $this->assertNotNull($writeEvent);

        $this->assertEquals(
            $storedPosts->first()->id,
            $posts->first()->id,
        );

        $this->assertEquals(
            $storedPosts->first()->id,
            $writeEvent->value->first()->id,
        );

        $this->assertEquals(
            $storedPosts->first()->id,
            $writeEvent->value->first()->id,
        );

        // Expect a cache hit this time.
        $storedPostsFromCache = DB::table('posts')->cacheQuery(now()->addHours(1))->get();
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostsFromCache->first()->id,
            $storedPosts->first()->id,
        );
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_get_with_columns()
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
                'select * from "posts"',
                $writeEvent->key,
            );
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent, &$writeEvent) {
            $hitEvent = $event;

            $this->assertSame([], $hitEvent->tags);
            $this->assertEquals($writeEvent->key, $hitEvent->key);
        });

        $posts = factory(Post::class, 30)->create();
        $storedPosts = DB::table('posts')->cacheQuery(now()->addHours(1))->get(['name']);

        $this->assertNotNull($writeEvent);

        $this->assertEquals(
            $storedPosts->first()->name,
            $posts->first()->name,
        );

        $this->assertEquals(
            $storedPosts->first()->name,
            $writeEvent->value->first()->name,
        );

        $this->assertEquals(
            $storedPosts->first()->name,
            $writeEvent->value->first()->name,
        );

        // Expect a cache hit this time.
        $storedPostsFromCache = DB::table('posts')->cacheQuery(now()->addHours(1))->get(['name']);
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostsFromCache->first()->name,
            $storedPosts->first()->name,
        );
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_get_with_string_columns()
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
                'select * from "posts"',
                $writeEvent->key,
            );
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent, &$writeEvent) {
            $hitEvent = $event;

            $this->assertSame([], $hitEvent->tags);
            $this->assertEquals($writeEvent->key, $hitEvent->key);
        });

        $posts = factory(Post::class, 30)->create();
        $storedPosts = DB::table('posts')->cacheQuery(now()->addHours(1))->get('name');

        $this->assertNotNull($writeEvent);

        $this->assertEquals(
            $storedPosts->first()->name,
            $posts->first()->name,
        );

        $this->assertEquals(
            $storedPosts->first()->name,
            $writeEvent->value->first()->name,
        );

        $this->assertEquals(
            $storedPosts->first()->name,
            $writeEvent->value->first()->name,
        );

        // Expect a cache hit this time.
        $storedPostsFromCache = DB::table('posts')->cacheQuery(now()->addHours(1))->get('name');
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostsFromCache->first()->name,
            $storedPosts->first()->name,
        );
    }
}
