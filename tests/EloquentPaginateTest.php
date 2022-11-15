<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Event;
use Rennokki\QueryCache\Test\Models\Post;

class EloquentPaginateTest extends EloquentTestCase
{
    /**
     * @dataProvider eloquentContextProvider
     */
    public function test_paginate()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        /** @var CacheHit|null $hitEvent */
        $hitEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            if (str_contains($event->key, 'select * from "posts" limit 15')) {
                $writeEvent = $event;

                $this->assertSame([], $writeEvent->tags);
                $this->assertEquals(3600, $writeEvent->seconds);
            }
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent, &$writeEvent) {
            if (str_contains($event->key, 'select * from "posts" limit 15')) {
                $hitEvent = $event;

                $this->assertSame([], $hitEvent->tags);
                $this->assertEquals($writeEvent->key, $hitEvent->key);
            }
        });

        $posts = factory(Post::class, 30)->create();
        $storedPosts = Post::cacheQuery(now()->addHours(1))->paginate(15);

        $this->assertNotNull($writeEvent);

        $this->assertEquals(
            $storedPosts->items()[0]->id,
            $posts->first()->id,
        );

        $this->assertEquals(
            $storedPosts->items()[0]->id,
            $writeEvent->value->first()->id,
        );

        $this->assertEquals(
            $storedPosts->items()[0]->id,
            $writeEvent->value->first()->id,
        );

        // Expect a cache hit this time.
        $storedPostsFromCache = Post::cacheQuery(now()->addHours(1))->paginate(15);
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostsFromCache->items()[0]->id,
            $storedPosts->items()[0]->id,
        );
    }

    /**
     * @dataProvider eloquentContextProvider
     */
    public function test_paginate_with_columns()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        /** @var CacheHit|null $hitEvent */
        $hitEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            if (str_contains($event->key, 'select * from "posts" limit 15')) {
                $writeEvent = $event;

                $this->assertSame([], $writeEvent->tags);
                $this->assertEquals(3600, $writeEvent->seconds);
            }
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent, &$writeEvent) {
            if (str_contains($event->key, 'select * from "posts" limit 15')) {
                $hitEvent = $event;

                $this->assertSame([], $hitEvent->tags);
                $this->assertEquals($writeEvent->key, $hitEvent->key);
            }
        });

        $posts = factory(Post::class, 30)->create();
        $storedPosts = Post::cacheQuery(now()->addHours(1))->paginate(15, ['name']);

        $this->assertNotNull($writeEvent);

        $this->assertEquals(
            $storedPosts->items()[0]->name,
            $posts->first()->name,
        );

        $this->assertEquals(
            $storedPosts->items()[0]->name,
            $writeEvent->value->first()->name,
        );

        $this->assertEquals(
            $storedPosts->items()[0]->name,
            $writeEvent->value->first()->name,
        );

        // Expect a cache hit this time.
        $storedPostsFromCache = Post::cacheQuery(now()->addHours(1))->paginate(15, ['name']);
        $this->assertNotNull($hitEvent);

        $this->assertEquals(
            $storedPostsFromCache->items()[0]->name,
            $storedPosts->items()[0]->name,
        );
    }
}
