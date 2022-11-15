<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Event;
use Rennokki\QueryCache\Test\Models\Post;

class EloquentGetTest extends EloquentTestCase
{
    /**
     * @dataProvider eloquentContextProvider
     */
    public function test_get()
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
        $storedPosts = Post::cacheQuery(now()->addHours(1))->get();

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
        $storedPostsFromCache = Post::cacheQuery(now()->addHours(1))->get();
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostsFromCache->first()->id,
            $storedPosts->first()->id,
        );
    }

    /**
     * @dataProvider eloquentContextProvider
     */
    public function test_get_with_columns()
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
        $storedPosts = Post::cacheQuery(now()->addHours(1))->get(['name']);

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
        $storedPostsFromCache = Post::cacheQuery(now()->addHours(1))->get(['name']);
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostsFromCache->first()->name,
            $storedPosts->first()->name,
        );
    }

    /**
     * @dataProvider eloquentContextProvider
     */
    public function test_get_with_string_columns()
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
        $storedPosts = Post::cacheQuery(now()->addHours(1))->get('name');

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
        $storedPostsFromCache = Post::cacheQuery(now()->addHours(1))->get('name');
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostsFromCache->first()->name,
            $storedPosts->first()->name,
        );
    }
}