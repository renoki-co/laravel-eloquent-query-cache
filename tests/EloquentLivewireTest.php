<?php

namespace Rennokki\QueryCache\Test;

use Livewire\Component;
use Livewire\Livewire;
use Rennokki\QueryCache\Test\Models\Post;

class EloquentLivewireTest extends EloquentTestCase
{
    /**
     * @dataProvider eloquentContextProvider
     */
    public function test_livewire_component_poll_doesnt_break_when_callback_is_already_set()
    {
        // See: https://github.com/renoki-co/laravel-eloquent-query-cache/issues/163
        Livewire::component(PostComponent::class);

        $posts = factory(Post::class, 30)->create();

        /** @var \Livewire\Testing\TestableLivewire $component */
        Livewire::test(PostComponent::class, ['post' => $posts->first()])
            ->assertOk()
            ->assertSee($posts[0]->name)
            ->pretendWereSendingAComponentUpdateRequest(
                'callMethod',
                ['id' => 'grwk', 'method' => '$refresh', 'params' => []],
            );
    }
}

class PostComponent extends Component
{
    public Post $post;

    public static function getName()
    {
        return 'post';
    }
}
