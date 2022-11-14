<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Rennokki\QueryCache\Test\Models\Page;

class FlushCacheOnUpdateTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_flush_cache_on_create()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $writeEvent = $event;

            if ($this->driverSupportsTags()) {
                $this->assertSame(['test'], $writeEvent->tags);
            }

            $this->assertEquals(3600, $writeEvent->seconds);

            $this->assertStringContainsString(
                'select * from "pages" limit 1',
                $writeEvent->key,
            );
        });

        factory(Page::class)->create();
        Page::cacheQuery(now()->addHours(1))->first();

        $this->assertNotNull($writeEvent);

        Page::create(['name' => '9GAG']);

        $this->assertNull(
            $this->driverSupportsTags()
                ? Cache::tags(['test'])->get($writeEvent->key)
                : Cache::get($writeEvent->key)
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_flush_cache_on_update()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $writeEvent = $event;

            if ($this->driverSupportsTags()) {
                $this->assertSame(['test'], $writeEvent->tags);
            }

            $this->assertEquals(3600, $writeEvent->seconds);

            $this->assertStringContainsString(
                'select * from "pages" limit 1',
                $writeEvent->key,
            );
        });

        factory(Page::class)->create();
        $page = Page::cacheQuery(now()->addHours(1))->first();

        $this->assertNotNull($writeEvent);

        $page->update(['name' => '9GAG']);

        $this->assertNull(
            $this->driverSupportsTags()
                ? Cache::tags(['test'])->get($writeEvent->key)
                : Cache::get($writeEvent->key)
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_flush_cache_on_delete()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $writeEvent = $event;

            if ($this->driverSupportsTags()) {
                $this->assertSame(['test'], $writeEvent->tags);
            }

            $this->assertEquals(3600, $writeEvent->seconds);

            $this->assertStringContainsString(
                'select * from "pages" limit 1',
                $writeEvent->key,
            );
        });

        factory(Page::class)->create();
        $page = Page::cacheQuery(now()->addHours(1))->first();

        $this->assertNotNull($writeEvent);

        $page->delete();

        $this->assertNull(
            $this->driverSupportsTags()
                ? Cache::tags(['test'])->get($writeEvent->key)
                : Cache::get($writeEvent->key)
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_flush_cache_on_force_deletion()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $writeEvent = $event;

            if ($this->driverSupportsTags()) {
                $this->assertSame(['test'], $writeEvent->tags);
            }

            $this->assertEquals(3600, $writeEvent->seconds);

            $this->assertStringContainsString(
                'select * from "pages" limit 1',
                $writeEvent->key,
            );
        });

        factory(Page::class)->create();
        $page = Page::cacheQuery(now()->addHours(1))->first();

        $this->assertNotNull($writeEvent);

        $page->forceDelete();

        $this->assertNull(
            $this->driverSupportsTags()
                ? Cache::tags(['test'])->get($writeEvent->key)
                : Cache::get($writeEvent->key)
        );
    }
}
