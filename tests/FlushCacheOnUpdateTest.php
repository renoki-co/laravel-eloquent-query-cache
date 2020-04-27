<?php

namespace Rennokki\QueryCache\Test;

use Cache;
use Rennokki\QueryCache\Test\Models\Page;
use Rennokki\QueryCache\Test\Models\Post;

class FlushCacheOnUpdateTest extends TestCase
{
    public function test_flush_cache_on_create()
    {
        $page = factory(Page::class)->create();
        $storedPage = Page::cacheFor(now()->addHours(1))->first();
        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "pages" limit 1a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPage->id
        );

        Page::create([
            'name' => '9GAG',
        ]);

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "pages" limit 1a:0:{}');

        $this->assertNull($cache);
    }

    public function test_flush_cache_on_update()
    {
        $page = factory(Page::class)->create();
        $storedPage = Page::cacheFor(now()->addHours(1))->first();
        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "pages" limit 1a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPage->id
        );

        $page->update([
            'name' => '9GAG',
        ]);

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "pages" limit 1a:0:{}');

        $this->assertNull($cache);
    }

    public function test_flush_cache_on_delete()
    {
        $page = factory(Page::class)->create();
        $storedPage = Page::cacheFor(now()->addHours(1))->first();
        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "pages" limit 1a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPage->id
        );

        $page->delete();

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "pages" limit 1a:0:{}');

        $this->assertNull($cache);
    }

    public function test_flush_cache_on_force_deletion()
    {
        $page = factory(Page::class)->create();
        $storedPage = Page::cacheFor(now()->addHours(1))->first();
        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "pages" limit 1a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPage->id
        );

        $page->forceDelete();

        $cache = Cache::tags(['test'])->get('leqc:sqlitegetselect * from "pages" limit 1a:0:{}');

        $this->assertNull($cache);
    }
}
