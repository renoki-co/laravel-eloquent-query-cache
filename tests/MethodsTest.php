<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Support\Str;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\Events\KeyWritten;
use Rennokki\QueryCache\Test\Models\Kid;
use Rennokki\QueryCache\Test\Models\Book;
use Rennokki\QueryCache\Test\Models\Post;
use Rennokki\QueryCache\Test\Models\User;

class MethodsTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_do_not_cache()
    {
        Event::listen(KeyWritten::class, function (KeyWritten $event) {
            $this->fail('The cache should not be written');
        });

        factory(Post::class, 10)->create();

        $this->assertSame(
            Post::cacheQuery(now()->addHours(1))->avoidCache()->get()->toArray(),
            Post::cacheQuery(now()->addHours(1))->avoidCache()->get()->toArray(),
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_cache_prefix()
    {
        $writePassed = false;
        $hitPassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writePassed) {
            $this->assertStringStartsWith('test', $event->key);
            $writePassed = true;
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitPassed) {
            $this->assertStringStartsWith('test', $event->key);
            $hitPassed = true;
        });

        factory(Post::class)->create();

        Post::cacheQuery(now()->addHours(1))->cachePrefix('test')->first();
        Post::cacheQuery(now()->addHours(1))->cachePrefix('test')->first();

        $this->assertTrue($writePassed && $hitPassed);
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_cache_tags()
    {
        $writePassed = false;
        $hitPassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writePassed) {
            if ($this->driverSupportsTags()) {
                $this->assertSame(['test'], $event->tags);
            }

            $writePassed = true;
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitPassed) {
            if ($this->driverSupportsTags()) {
                $this->assertSame(['test'], $event->tags);
            }

            $hitPassed = true;
        });

        factory(Post::class)->create();

        Post::cacheQuery(now()->addHours(1))->cacheTags(['test'])->first();
        Post::cacheQuery(now()->addHours(1))->cacheTags(['test'])->first();

        $this->assertTrue($writePassed && $hitPassed);
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_cache_flush_with_the_right_tag()
    {
        $flushPassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$flushPassed) {
            if ($this->driverSupportsTags()) {
                $this->assertEquals(['test'], $event->tags);
            }

            $this->assertTrue(Post::flushQueryCache(['test']));

            if ($this->driverSupportsTags()) {
                $this->assertNull(Cache::tags(['test'])->get($event->key));
            } else {
                $this->assertNull(Cache::get($event->key));
            }

            $flushPassed = true;
        });

        factory(Post::class)->create();

        Post::cacheQuery(now()->addHours(1))
            ->cacheTags(['test'])
            ->first();

        $this->assertTrue($flushPassed);
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_cache_flush_without_the_right_tag()
    {
        $flushPassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$flushPassed) {
            if ($this->driverSupportsTags()) {
                $this->assertEquals(['test'], $event->tags);
            }

            $this->assertTrue(Post::flushQueryCache(['production']));

            if ($this->driverSupportsTags()) {
                $this->assertNotNull(
                    Cache::tags(['test'])->get($event->key),
                );
            } else {
                $this->assertNull(
                    Cache::get($event->key)
                );
            }

            $flushPassed = true;
        });

        factory(Post::class)->create();

        Post::cacheQuery(now()->addHours(1))
            ->cacheTags(['test'])
            ->first();

        $this->assertTrue($flushPassed);
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_cache_flush_with_default_tags_attached()
    {
        $flushPassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$flushPassed) {
            if ($this->driverSupportsTags()) {
                $this->assertEquals(['test', Book::class], $event->tags);
            }

            $this->assertTrue(Book::flushQueryCache());

            if ($this->driverSupportsTags()) {
                $this->assertNull(Cache::tags(['test', Book::class])->get($event->key));
                $this->assertNull(Cache::tags([Book::class])->get($event->key));
                $this->assertNull(Cache::tags(['test'])->get($event->key));
            }

            $this->assertNull(Cache::get($event->key));

            $flushPassed = true;
        });

        factory(Book::class)->create();

        Book::cacheQuery(now()->addHours(1))
            ->cacheTags(['test'])
            ->first();

        $this->assertTrue($flushPassed);
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_hashed_key()
    {
        $writePassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writePassed) {
            $this->assertStringStartsWith('leqc', $event->key);
            $this->assertEquals(64, strlen(Str::after($event->key, 'leqc:')));
            $writePassed = true;
        });

        factory(Kid::class)->create();

        Kid::cacheQuery(now()->addHours(1))
            ->withPlainKey(false)
            ->first();

        $this->assertTrue($writePassed);
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_append_cache_tags()
    {
        $appendPassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$appendPassed) {
            if ($this->driverSupportsTags()) {
                $this->assertEquals(['test', 'test2', Book::class], $event->tags);
            }

            $appendPassed = true;
        });

        factory(Book::class)->create();

        Book::cacheQuery(now()->addHours(1))
            ->appendCacheTags(['test'])
            ->appendCacheTags(['test2'])
            ->first();

        $this->assertTrue($appendPassed);
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_append_cache_tags_with_sub_query()
    {
        $user = factory(User::class)->create();

        factory(Post::class)->createMany([
            ['user_id' => $user->id, 'name' => 'Post 1 on topic 1'],
            ['user_id' => $user->id, 'name' => 'Post 2 on topic 1'],
            ['user_id' => $user->id, 'name' => 'Post 3 on topic 2'],
        ]);

        $userAndPosts = User::cacheQuery(now()->addHours(1))
            ->withCount([
                'posts' => function ($query) {
                    $query->cacheQuery()
                        ->appendCacheTags(['posts'])
                        ->where('name', 'like', '%topic 1%');
                },
            ])
            ->appendCacheTags(['user']);

        $this->assertEquals(['user'], $userAndPosts->getCacheTags());
    }
}
