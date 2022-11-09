<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Support\Facades\Cache;
use Rennokki\QueryCache\Test\Models\Post;

class FirstTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_first()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::cacheFor(now()->addHours(1))->first();
        $cache = Cache::get('leqc:sqlitegetselect * from "posts" limit 1a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPost->id
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_first_with_columns()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::cacheFor(now()->addHours(1))->first(['name']);
        $cache = Cache::get('leqc:sqlitegetselect "name" from "posts" limit 1a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->name,
            $storedPost->name
        );
    }
}
