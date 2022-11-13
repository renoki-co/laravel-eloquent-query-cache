<?php

namespace Rennokki\QueryCache\Test;

use Rennokki\QueryCache\Test\Models\Role;
use Rennokki\QueryCache\Test\Models\User;

class FlushCacheOnUpdatePivotTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_belongs_to_many()
    {
        $key = 'leqc:sqlitegetselect "roles".*, "role_user"."user_id" as "pivot_user_id", "role_user"."role_id" as "pivot_role_id" from "roles" inner join "role_user" on "roles"."id" = "role_user"."role_id" where "role_user"."user_id" = ? limit 1a:1:{i:0;i:1;}';

        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $storedRoles = $user->roles()->cacheFor(now()->addHours(1))->cacheTags(["user:{$user->id}:roles"])->get();
        $cache = $this->getCacheWithTags($key, ["user:{$user->id}:roles"]);

        $this->assertNull($cache);
        $this->assertEquals(0, $storedRoles->count());

        $user->roles()->attach($role->id);

        $storedRoles = $user->roles()->cacheFor(now()->addHours(1))->cacheTags(["user:{$user->id}:roles"])->get();

        $this->assertEquals(
            $role->id,
            $storedRoles->first()->id
        );

        $user->roles()->detach($role->id);

        $storedRoles = $user->roles()->cacheFor(now()->addHours(1))->cacheTags(["user:{$user->id}:roles"])->get();

        $this->assertEquals(0, $storedRoles->count());
    }
}
