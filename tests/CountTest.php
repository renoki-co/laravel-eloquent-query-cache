<?php

namespace Rennokki\QueryCache\Test;

use Rennokki\QueryCache\Test\Models\Post;

class CountTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_count()
    {
        $posts = factory(Post::class, 5)->create();
        $postsCount = Post::cacheFor(now()->addHours(1))->count();
        $cache = $this->getCountCache('count(*)');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->aggregate,
            $postsCount
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_count_with_columns()
    {
        $posts = factory(Post::class, 5)->create();
        $postsCount = Post::cacheFor(now()->addHours(1))->count('name');
        $cache = $this->getCountCache('count("name")');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->aggregate,
            $postsCount
        );
    }
}
