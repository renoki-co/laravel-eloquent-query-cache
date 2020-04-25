Laravel Eloquent Query Cache
===================================

[![Build Status](https://travis-ci.com/rennokki/laravel-eloquent-query-cache.svg?branch=master)](https://travis-ci.com/rennokki/laravel-eloquent-query-cache)
[![codecov](https://codecov.io/gh/rennokki/laravel-eloquent-query-cache/branch/master/graph/badge.svg)](https://codecov.io/gh/rennokki/laravel-eloquent-query-cache/branch/master)
[![StyleCI](https://github.styleci.io/repos/223236785/shield?branch=master)](https://github.styleci.io/repos/223236785)
[![Latest Stable Version](https://poser.pugx.org/rennokki/laravel-eloquent-query-cache/v/stable)](https://packagist.org/packages/rennokki/laravel-eloquent-query-cache)
[![Total Downloads](https://poser.pugx.org/rennokki/laravel-eloquent-query-cache/downloads)](https://packagist.org/packages/rennokki/laravel-eloquent-query-cache)
[![Monthly Downloads](https://poser.pugx.org/rennokki/laravel-eloquent-query-cache/d/monthly)](https://packagist.org/packages/rennokki/laravel-eloquent-query-cache)
[![License](https://poser.pugx.org/rennokki/laravel-eloquent-query-cache/license)](https://packagist.org/packages/rennokki/laravel-eloquent-query-cache)

Laravel Eloquent Query Cache brings back the `remember()` functionality that has been removed from Laravel a long time ago.
It adds caching functionalities directly on the Eloquent level, making use of cache within your database queries.

## Installing the package

Hop into your console and install the package via Composer:

```bash
$ composer require rennokki/laravel-eloquent-query-cache
```

Each model you want cache on should use the `Rennokki\QueryCache\Traits\QueryCacheable` trait.

```php
use Rennokki\QueryCache\Traits\QueryCacheable;

class Podcast extends Model
{
    use QueryCacheable;

    ...
}
```

## Showcase

The package has the ability to track the SQL used and use it as a key in the cache storage,
making the caching query-by-query a breeze.

```php
use Rennokki\QueryCache\Traits\QueryCacheable;

class Article extends Model
{
    use QueryCacheable;

    public $cacheFor = 3600; // cache time, in seconds
    ...
}

// SELECT * FROM articles ORDER BY created_at DESC LIMIT 1;
$latestArticle = Article::latest()->first();

// SELECT * FROM articles WHERE published = 1;
$publishedArticles = Article::wherePublished(true)->get();
```

In the above example, both queries have different keys in the cache storage, thus it doesn't matter what query we handle. By default, caching is disabled unless you specify a value for `$cacheFor`. As long as `$cacheFor` is existent and is greater than `0`, all queries will be cached.

It is also possible to enable caching for specific queries by not specifying `$cacheFor` and calling `cacheFor()` within your queries:

```php
$postsCount = Post::cacheFor(60 * 60)->count();

// Using a DateTime instance like Carbon works perfectly fine!
$postsCount = Post::cacheFor(now()->addDays(1))->count();
```

## Cache Tags & Cache Invalidation

Some caching stores accept tags. This is really useful if you plan on tagging your cached queries and invalidate only some of the queries when needed.

```php
$shelfOneBooks = Book::whereShelf(1)
    ->cacheFor(60)
    ->cacheTags(['shelf:1'])
    ->get();

$shelfTwoBooks = Book::whereShelf(2)
    ->cacheFor(60)
    ->cacheTags(['shelf:2'])
    ->get();

// After flushing the cache for shelf:1, the query of$shelfTwoBooks will still hit the cache if re-called again.
Book::flushQueryCache(['shelf:1']);

// Flushing also works for both tags, invalidating them both, not just the one tagged with shelf:1
Book::flushQueryCache(['shelf:1', 'shelf:2']);
```

Be careful tho - specifying cache tags does not change the behaviour of key storage.
For example, the following two queries, altough the use the same tag, they have different keys stored in the caching database.

```php
$alice = Kid::whereName('Alice')
    ->cacheFor(60)
    ->cacheTags(['kids'])
    ->first();

$bob = Kid::whereName('Bob')
    ->cacheFor(60)
    ->cacheTags(['kids'])
    ->first();
```

### Global Cache Invalidation

To invalidate all the cache for a specific model, use the `flushQueryCache` method without passing the tags.

The package automatically appends a list of tags, called **base tags** on each query coming from a model. It defaults to the full model class name.

In case you want to change the base tags, you can do so in your model.

```php
class Kid extends Model
{
    use QueryCacheable;

    /**
     * Set the base cache tags that will be present
     * on all queries.
     *
     * @return array
     */
    protected function getCacheBaseTags(): array
    {
        return [
            'custom_tag',
        ];
    }
}

// Automatically works with `custom_tag`
Kid::flushQueryCache();
```

## Relationship Caching

Relationships are just another queries. They can be intercepted and modified before the database is hit with the query. The following example needs the `Order` model (or the model associated with the `orders` relationship) to include the `QueryCacheable` trait.

```php
$user = User::with(['orders' => function ($query) {
    return $query
        ->cacheFor(60 * 60)
        ->cacheTags(['my:orders']);
}])->get();

// This comes from the cache if existed.
$orders = $user->orders;
```

## Cache Keys

The package automatically generate the keys needed to store the data in the cache store. However, prefixing them might be useful if the cache store is used by other applications and/or models and you want to manage the keys better to avoid collisions.

```php
$bob = Kid::whereName('Bob')
    ->cacheFor(60)
    ->cachePrefix('kids_')
    ->first();
```

If no prefix is specified, the string `leqc` is going to be used.

## Cache Drivers

By default, the trait uses the default cache driver. If you want to **force** a specific one, you can do so by calling `cacheDriver()`:

```php
$bob = Kid::whereName('Bob')
    ->cacheFor(60)
    ->cacheDriver('dynamodb')
    ->first();
```

## Disable caching

If you enabled caching (either by model variable or by the `cacheFor` scope), you can also opt to disable it within your query builder chains:

```php
$uncachedBooks = Book::dontCache()->get();
$uncachedBooks = Book::doNotCache()->get(); // same thing
```

## Equivalent Methods and Variables

You can use the methods provided in this documentation query-by-query, or you can set defaults for each one in the model; using the methods query-by-query will overwrite the defaults.
While settings defaults is not mandatory (excepting for `$cacheFor` that will enable caching on **all** queries), it can be useful to avoid using the chained methods on each query.

```php
class Book extends Model
{
    public $cacheFor = 3600; // equivalent of ->cacheFor(3600)

    public $cacheTags = ['books']; // equivalent of ->cacheTags(['books'])

    public $cachePrefix = 'books_' // equivalent of ->cachePrefix('books_');

    public $cacheDriver = 'dynamodb'; // equivalent of ->cacheDriver('dynamodb');
}
```

## Implement the caching method to your own Builder class

Since this package modifies the `newBaseQueryBuilder()` in the model, having multiple traits that
modify this function will lead to an overlap.

This can happen in case you are creating your own Builder class for another database drivers or simply to ease out your app query builder for more flexibility.

To solve this, all you have to do is to add the `\Rennokki\QueryCache\Traits\QueryCacheModule` trait and the `\Rennokki\QueryCache\Contracts\QueryCacheModuleInterface` interface to your `Builder` class. Make sure that the model will no longer use the original `QueryCacheable` trait.

```php
use Rennokki\QueryCache\Traits\QueryCacheModule;
use Illuminate\Database\Query\Builder as BaseBuilder; // the base laravel builder
use Rennokki\QueryCache\Contracts\QueryCacheModuleInterface;

// MyCustomBuilder.php
class MyCustomBuilder implements QueryCacheModuleInterface
{
    use QueryCacheModule;

    // the rest of the logic here.
}

// MyBuilderTrait.php
trait MyBuilderTrait
{
    protected function newBaseQueryBuilder()
    {
        return new MyCustomBuilder(
            //
        );
    }
}

// app/CustomModel.php
class CustomModel extends Model
{
    use MyBuilderTrait;
}

CustomModel::cacheFor(30)->customGetMethod();
```

## Generating your own key

This is how the default key generation function looks like:

```php
public function generatePlainCacheKey(string $method = 'get', $id = null, $appends = null): string
{
    $name = $this->connection->getName();

    // Count has no Sql, that's why it can't be used ->toSql()
    if ($method === 'count') {
        return $name.$method.$id.serialize($this->getBindings()).$appends;
    }

    return $name.$method.$id.$this->toSql().serialize($this->getBindings()).$appends;
}
```

In some cases, like implementing your own Builder for MongoDB for example, you might not want to use the `toSql()` and use your own
method of generating per-sql key. You can do so by overwriting the `MyCustomBuilder` class `generatePlainCacheKey()` with your own one.

It is, however, highly recommended to use the most of the variables provided by the function to avoid cache overlapping issues.

```php
class MyCustomBuilder implements QueryCacheModuleInterface
{
    use QueryCacheModule;

    public function generatePlainCacheKey(string $method = 'get', $id = null, $appends = null): string
    {
        $name = $this->connection->getName();

        // Using ->myCustomSqlString() instead of ->toSql()
        return $name.$method.$id.$this->myCustomSqlString().serialize($this->getBindings()).$appends;
    }
}
```

## Implementing cache for other functions than get()

Since all of the Laravel Eloquent functions are based on it, the builder that comes with this package replaces only the `get()` one:

```php
class Builder
{
    public function get($columns = ['*'])
    {
        if (! $this->shouldAvoidCache()) {
            return $this->getFromQueryCache('get', $columns);
        }

        return parent::get($columns);
    }
}
```

In case that you want to cache your own methods from your custom builder or, for instance, your `count()` method doesn't rely on `get()`, you can replace it using this syntax:

```php
class MyCustomBuilder
{
    public function count()
    {
        if (! $this->shouldAvoidCache()) {
            return $this->getFromQueryCache('count');
        }

        return parent::count();
    }
}
```

In fact, you can also replace any eloquent method within your builder if you use `$this->shouldAvoidCache()` check and retrieve the cached data using `getFromQueryCache()` method, passing the method name as string, and, optionally, an array of columns that defaults to `['*']`.

Notice that the `getFromQueryCache()` method accepts a method name and a `$columns` parameter. If your method doesn't implement the `$columns`, don't pass it.

Note that some functions like `getQueryCacheCallback()` may come with an `$id` parameter.
The default behaviour of the package doesn't use it, since the query builder uses `->get()` by default that accepts only columns.

However, if your builder replaces functions  like `find()`, `$id` is needed and you will also have to replace the `getQueryCacheCallback()` like so:

```php
class MyCustomBuilder
{
    public function getQueryCacheCallback(string $method = 'get', $columns = ['*'], $id = null)
    {
        return function () use ($method, $columns, $id) {
            $this->avoidCache = true;

            // the function for find() caching
            // accepts different params
            if ($method === 'find') {
                return $this->find($id, $columns);
            }

            return $this->{$method}($columns);
        };
    }

    public function find($id, $columns = ['*'])
    {
        // implementing the same logic
        if (! $this->shouldAvoidCache()) {
            return $this->getFromQueryCache('find', $columns, $id);
        }

        return parent::find($id, $columns);
    }
}
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email alex@renoki.org instead of using the issue tracker.

## Credits

- [Alex Renoki](https://github.com/rennokki)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
