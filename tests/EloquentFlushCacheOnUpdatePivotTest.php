<?php

namespace Rennokki\QueryCache\Test;

use Illuminate\Support\Facades\Event;
use Illuminate\Cache\Events\KeyWritten;
use Rennokki\QueryCache\Test\Models\Role;
use Rennokki\QueryCache\Test\Models\User;

class EloquentFlushCacheOnUpdatePivotTest extends EloquentTestCase
{
    /**
     * @dataProvider eloquentContextProvider
     */
    public function test_belongs_to_many()
    {
        $hasRole = false;

        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$hasRole, $user) {
            if ($hasRole) {
                $this->assertEquals(
                    $user->roles()->first()->id,
                    $event->value->first()?->id,
                );
            } else {
                $this->assertNull($user->roles()->first());
                $this->assertNull($event->value->first());
            }

            if ($this->driverSupportsTags()) {
                $this->assertEquals(['user:1:roles'], $event->tags);
            }

            $this->assertStringContainsString(
                'inner join "role_user"',
                $event->key,
            );
        });

        $userRoles = $user->roles()
            ->cacheFor(now()->addHours(1))
            ->cacheTags(["user:{$user->id}:roles"])
            ->get();

        $this->assertEquals(0, $userRoles->count());

        $user->roles()->attach($role->id);
        $hasRole = $user->roles()->count() > 0;

        $userRolesAfterAttach = $user->roles()
            ->cacheFor(now()->addHours(1))
            ->cacheTags(["user:{$user->id}:roles"])
            ->get();

        $this->assertEquals(
            $role->id,
            $userRolesAfterAttach->first()->id
        );

        $user->roles()->detach($role->id);
        $hasRole = $user->roles()->count() > 0;

        $userRolesAfterDetach = $user->roles()
            ->cacheFor(now()->addHours(1))
            ->cacheTags(["user:{$user->id}:roles"])
            ->get();

        $this->assertEquals(0, $userRolesAfterDetach->count());
    }
}
