<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Support\Str;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\DB;
use Rennokki\QueryCache\QueryCache;
use Rennokki\QueryCache\Test\Models\Kid;
use Rennokki\QueryCache\Test\Models\Book;
use Rennokki\QueryCache\Test\Models\Post;
use Rennokki\QueryCache\Test\Models\User;

class DbMethodsTest extends DbTestCase
{
    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_do_not_cache()
    {
        Event::listen(KeyWritten::class, function (KeyWritten $event) {
            $this->fail('The cache should not be written');
        });

        factory(Post::class, 10)->create();

        $this->assertSame(
            DB::table('posts')->cacheQuery(now()->addHours(1))->avoidCache()->get()->toJson(),
            DB::table('posts')->cacheQuery(now()->addHours(1))->avoidCache()->get()->toJson(),
        );
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_cache_prefix()
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

        DB::table('posts')->cacheQuery(now()->addHours(1))->cachePrefix('test')->first();
        DB::table('posts')->cacheQuery(now()->addHours(1))->cachePrefix('test')->first();

        $this->assertTrue($writePassed && $hitPassed);
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_cache_tags()
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

        DB::table('posts')->cacheQuery(now()->addHours(1))->cacheTags(['test'])->first();
        DB::table('posts')->cacheQuery(now()->addHours(1))->cacheTags(['test'])->first();

        $this->assertTrue($writePassed && $hitPassed);
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_cache_flush_with_the_right_tag()
    {
        $flushPassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$flushPassed) {
            if ($this->driverSupportsTags()) {
                $this->assertEquals(['test'], $event->tags);
            }

            $this->assertTrue(DB::flushQueryCache(['test']));

            if ($this->driverSupportsTags()) {
                $this->assertNull(Cache::tags(['test'])->get($event->key));
            } else {
                $this->assertNull(Cache::get($event->key));
            }

            $flushPassed = true;
        });

        factory(Post::class)->create();

        DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->cacheTags(['test'])
            ->first();

        $this->assertTrue($flushPassed);
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_cache_flush_without_specifying_cache_tags()
    {
        $flushPassed = false;

        QueryCache::cacheBaseTags(['test']);

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$flushPassed) {
            if ($this->driverSupportsTags()) {
                $this->assertEquals(['test'], $event->tags);
            }

            $this->assertTrue(DB::flushQueryCache());

            if ($this->driverSupportsTags()) {
                $this->assertNull(Cache::tags(['test', 'books'])->get($event->key));
                $this->assertNull(Cache::tags(['books'])->get($event->key));
                $this->assertNull(Cache::tags(['test'])->get($event->key));
            }

            $this->assertNull(Cache::get($event->key));

            $flushPassed = true;
        });

        factory(Book::class)->create();

        DB::table('books')
            ->cacheQuery(now()->addHours(1))
            ->cacheTags(['test'])
            ->first();

        $this->assertTrue($flushPassed);
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_hashed_key()
    {
        $writePassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writePassed) {
            $this->assertStringStartsWith('leqc', $event->key);
            $this->assertEquals(64, strlen(Str::after($event->key, 'leqc:')));
            $writePassed = true;
        });

        factory(Kid::class)->create();

        DB::table('kids')
            ->cacheQuery(now()->addHours(1))
            ->withPlainKey(false)
            ->first();

        $this->assertTrue($writePassed);
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_append_cache_tags()
    {
        $appendPassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$appendPassed) {
            if ($this->driverSupportsTags()) {
                $this->assertEquals(['test', 'test2'], $event->tags);
            }

            $appendPassed = true;
        });

        factory(Book::class)->create();

        DB::table('books')
            ->cacheQuery(now()->addHours(1))
            ->appendCacheTags(['test'])
            ->appendCacheTags(['test2'])
            ->first();

        $this->assertTrue($appendPassed);
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_raw_db_cache_doesnt_flush_with_other_tags()
    {
        $flushPassed = false;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$flushPassed) {
            if ($this->driverSupportsTags()) {
                $this->assertEquals(['test'], $event->tags);
            }

            $this->assertTrue(DB::flushQueryCache(['production']));

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

        DB::table('posts')
            ->cacheQuery(now()->addHours(1))
            ->cacheTags(['test'])
            ->first();

        $this->assertTrue($flushPassed);
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_writing_raw_with_cache_should_not_crash()
    {
        DB::table('posts')->cacheQuery()->insert([
            'name' => 'Example Post',
        ]);

        $this->assertCount(1, DB::table('posts')->get());
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_macros_transfer_for_raw_queries()
    {
        QueryBuilder::macro('custom', function () {
            /** @var QueryBuilder $this */
            return $this->where('name', '9GAG');
        });

        $this->assertStringContainsString(
            'from "pages" where "name" = ?',
            DB::table('pages')->custom()->toSql(),
        );

        $this->assertStringContainsString(
            'from "pages" where "name" = ?',
            DB::table('pages')->cacheQuery()->custom()->toSql(),
        );

        $this->assertStringContainsString(
            'from "pages" where "name" = ?',
            DB::table('pages')->custom()->cacheQuery()->toSql(),
        );
    }

    /**
     * @dataProvider databaseContextProvider
     */
    public function test_global_cacheTags_does_not_duplicate_final_tags()
    {
        QueryCache::cacheTags(['test']);
        $this->test_raw_db_cache_tags();
    }
}
