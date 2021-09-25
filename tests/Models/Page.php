<?php

namespace Rennokki\QueryCache\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Page extends Model
{
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true;

    protected $cacheUsePlainKey = true;

    protected $fillable = [
        'name',
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
}
