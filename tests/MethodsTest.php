<?php

namespace Rennokki\QueryCache\Test;

use Cache;
use Rennokki\QueryCache\Test\Models\Book;
use Rennokki\QueryCache\Test\Models\Kid;
use Rennokki\QueryCache\Test\Models\Post;

class MethodsTest extends TestCase
{
    public function test_do_not_cache()
    {
        $post = factory(Post::class)->create();

        $storedPost = Post::cacheFor(now()->addHours(1))->doNotCache()->first();
        $cache = Cache::get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNull($cache);

        $storedPost = Post::cacheFor(now()->addHours(1))->dontCache()->first();
        $cache = Cache::get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNull($cache);
    }

    public function test_cache_prefix()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::cacheFor(now()->addHours(1))->cachePrefix('test')->first();
        $cache = Cache::get('test:sqlitegetselect * from "posts" limit 1a:0:{}');

        $this->assertNotNull($cache);
    }

    public function test_cache_tags()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::cacheFor(now()->addHours(1))->cacheTags(['test'])->first();

        $cache = Cache::get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNull($cache);

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNotNull($cache);
    }

    public function test_cache_flush_with_the_right_tag()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::cacheFor(now()->addHours(1))->cacheTags(['test'])->first();

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNotNull($cache);

        Post::flushQueryCache(['test']);

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNull($cache);
    }

    public function test_cache_flush_without_the_right_tag()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::cacheFor(now()->addHours(1))->cacheTags(['test'])->first();

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNotNull($cache);

        Post::flushQueryCache(['test2']);
        Post::flushQueryCacheWithTag('test2');

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNotNull($cache);
    }

    public function test_cache_flush_with_more_tags()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::cacheFor(now()->addHours(1))->cacheTags(['test'])->first();

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNotNull($cache);

        Post::flushQueryCache([
            'test',
            'test2',
            'test3',
        ]);

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNull($cache);
    }

    public function test_cache_flush_with_default_tags_attached()
    {
        $book = factory(Book::class)->create();
        $storedBook = Book::cacheFor(now()->addHours(1))->cacheTags(['test'])->first();

        $cache = Cache::tags(['test', Book::getCacheBaseTags()[0]])->get('leqc:sqlitegetselect * from "books" limit 1a:0:{}');
        $this->assertNotNull($cache);

        Book::flushAllQueryCache();

        $cache = Cache::tags(['test', Book::getCacheBaseTags()[0]])->get('leqc:sqlitegetselect * from "books" limit 1a:0:{}');

        $this->assertNull($cache);
    }

    public function test_hashed_key()
    {
        $kid = factory(Kid::class)->create();
        $storedKid = Kid::cacheFor(now()->addHours(1))->first();
        $cache = Cache::get('leqc:156667fa9bcb7fb8abb01018568648406f251ef65736e89e6fd27d08bc48b5bb');

        $this->assertNotNull($cache);
    }
}
