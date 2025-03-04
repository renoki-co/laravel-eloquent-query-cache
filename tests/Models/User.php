<?php

namespace Rennokki\QueryCache\Test\Models;

use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Chelout\RelationshipEvents\Traits\HasRelationshipObservables;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Rennokki\QueryCache\Traits\QueryCacheable;

class User extends Authenticatable
{
    use HasBelongsToManyEvents;
    use HasRelationshipObservables;
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true;

    protected $cacheUsePlainKey = true;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function getCacheBaseTags(): array
    {
        return [
            'test',
        ];
    }

    protected function cacheUsePlainKeyValue()
    {
        return $this->cacheUsePlainKey;
    }

    protected function cacheForValue()
    {
        return 3600;
    }

    public function getCacheTagsToInvalidateOnUpdate($relation = null, ?Collection $pivotedModels = null): array
    {
        if ($relation === 'roles') {
            $tags = array_reduce($pivotedModels->all(), function ($tags, Role $role) {
                return array_merge($tags, ["user:{$this->id}:roles:{$role->id}"]);
            }, []);

            return array_merge($tags, [
                "user:{$this->id}:roles",
            ]);
        }

        return $this->getCacheBaseTags();
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
