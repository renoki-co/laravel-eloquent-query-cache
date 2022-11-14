<?php

namespace Rennokki\QueryCache\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Page extends Model
{
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true;

    public $cacheUsePlainKey = true;

    protected $fillable = [
        'name',
    ];

    public function getCacheBaseTags(): array
    {
        return [
            'test',
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
