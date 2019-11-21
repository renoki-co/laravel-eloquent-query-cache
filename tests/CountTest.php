<?php

namespace Rennokki\QueryCache\Test;

use Cache;
use Rennokki\QueryCache\Test\Models\Post;

class CountTest extends TestCase
{
    public function test_count()
    {
        $posts = factory(Post::class, 5)->create();
        $postsCount = Post::cacheFor(now()->addHours(1))->count();
        $cache = Cache::get('leqc:sqlitegetselect count(*) as aggregate from "posts"a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->aggregate,
            $postsCount
        );
    }

    public function test_count_with_columns()
    {
        $posts = factory(Post::class, 5)->create();
        $postsCount = Post::cacheFor(now()->addHours(1))->count('name');
        $cache = Cache::get('leqc:sqlitegetselect count("name") as aggregate from "posts"a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->aggregate,
            $postsCount
        );
    }
}
