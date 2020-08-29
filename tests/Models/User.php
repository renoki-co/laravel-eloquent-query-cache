<?php

namespace Rennokki\QueryCache\Test\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Rennokki\QueryCache\Traits\QueryCacheable;

class User extends Authenticatable
{
    use QueryCacheable;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function getCacheBaseTags(): array
    {
        return [
            //
        ];
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
