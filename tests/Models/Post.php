<?php

namespace Rennokki\QueryCache\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Post extends Model
{
    use QueryCacheable;

    public $cacheUsePlainKey = true;

    protected $fillable = [
        'name',
    ];

    public function getCacheBaseTags(): array
    {
        return [
            //
        ];
    }

    public function cacheUsePlainKeyValue()
    {
        return $this->cacheUsePlainKey;
    }

    public function cacheForValue()
    {
        return 3600;
    }
}
