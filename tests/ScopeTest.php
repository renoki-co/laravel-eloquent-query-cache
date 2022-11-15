<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Event;
use Rennokki\QueryCache\Test\Models\Post;

class ScopeTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Post::clearBootedModels();

        parent::tearDown();
    }

    public function test_local_scope()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        /** @var CacheHit|null $hitEvent */
        $hitEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $this->assertStringContainsString('where "name" = ? limit 1', $event->key);
            $writeEvent = $event;
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent) {
            $this->assertStringContainsString('where "name" = ? limit 1', $event->key);
            $hitEvent = $event;
        });

        $posts = factory(Post::class, 30)->create();

        // Hit database & cache.
        $storedPost = Post::cacheQuery(now()->addHours(1))
            ->customNameLocalScope($posts[1]->name)
            ->first();

        $this->assertEquals(2, $storedPost->id);
        $this->assertNotNull($writeEvent);

        // Hit cache.
        $storedPost = Post::cacheQuery(now()->addHours(1))
            ->customNameLocalScope($posts[1]->name)
            ->first();

        $this->assertEquals(2, $storedPost->id);
        $this->assertNotNull($hitEvent);
    }

    public function test_global_scope()
    {
        /** @var KeyWritten|null $writeEvent */
        $writeEvent = null;

        /** @var CacheHit|null $hitEvent */
        $hitEvent = null;

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writeEvent) {
            $this->assertStringContainsString('where "name" = ? limit 1', $event->key);
            $writeEvent = $event;
        });

        Event::listen(CacheHit::class, function (CacheHit $event) use (&$hitEvent) {
            $this->assertStringContainsString('where "name" = ? limit 1', $event->key);
            $hitEvent = $event;
        });

        $posts = factory(Post::class, 30)->create();

        Post::addGlobalScope(new CustomNameScope($posts[1]->name));

        // Hit database & cache.
        $storedPost = Post::cacheQuery(now()->addHours(1))->first();
        $this->assertEquals(2, $storedPost->id);
        $this->assertNotNull($writeEvent);

        // Hit cache.
        $storedPost = Post::cacheQuery(now()->addHours(1))->first();
        $this->assertEquals(2, $storedPost->id);
        $this->assertNotNull($hitEvent);
    }
}

class CustomNameScope implements Scope
{
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function apply(Builder $builder, Model $model)
    {
        $builder->where('name', $this->name);
    }
}
