Laravel Eloquent Query Cache
===================================

[![Build Status](https://travis-ci.org/rennokki/laravel-eloquent-query-cache.svg?branch=master)](https://travis-ci.org/rennokki/laravel-eloquent-query-cache)
[![codecov](https://codecov.io/gh/rennokki/laravel-eloquent-query-cache/branch/master/graph/badge.svg)](https://codecov.io/gh/rennokki/laravel-eloquent-query-cache/branch/master)
[![StyleCI](https://github.styleci.io/repos/223236785/shield?branch=master)](https://github.styleci.io/repos/223236785)
[![Latest Stable Version](https://poser.pugx.org/rennokki/laravel-eloquent-query-cache/v/stable)](https://packagist.org/packages/rennokki/laravel-eloquent-query-cache)
[![Total Downloads](https://poser.pugx.org/rennokki/laravel-eloquent-query-cache/downloads)](https://packagist.org/packages/rennokki/laravel-eloquent-query-cache)
[![Monthly Downloads](https://poser.pugx.org/rennokki/laravel-eloquent-query-cache/d/monthly)](https://packagist.org/packages/rennokki/laravel-eloquent-query-cache)
[![License](https://poser.pugx.org/rennokki/laravel-eloquent-query-cache/license)](https://packagist.org/packages/rennokki/laravel-eloquent-query-cache)

[![PayPal](https://img.shields.io/badge/PayPal-donate-blue.svg)](https://paypal.me/rennokki)

Laravel Eloquent Query Cache (LEQC; Le QC; Le Query Cache) is a package that brings the `remember()` functionality that has been removed from Laravel a long time ago.
This package helps adding caching functionalities directly on the Eloquent level, making use of cache before retrieving the data from the DB.

This package adds caching support for **all** query methods.

## Installing the package
Hop into your console and install the package via Composer:

```bash
$ composer require rennokki/laravel-eloquent-query-cache
```

Each model that will accept query-by-query caching will have to use the `Rennokki\QueryCache\Traits\QueryCacheable` trait.

```php
use Rennokki\QueryCache\Traits\QueryCacheable;

class Podcast extends Model
{
    use QueryCacheable;

    ...
}
```

## Showcase
Query Cache has the ability to track the SQL used and use it as a key in the cache storage, making the caching query-by-query a breeze.

```php
use Rennokki\QueryCache\Traits\QueryCacheable;

class Article extends Model
{
    use QueryCacheable;

    $cacheFor = 3600; // cache time, in seconds
    ...
}

// SELECT * FROM articles ORDER BY created_at DESC LIMIT 1;
$latestArticle = Article::latest()->first();

// SELECT * FROM articles WHERE published = 1;
$publishedArticles = Article::wherePublished(true)->get();
```

In the above example, both queries have different keys in the cache storage, thus it doesn't matter what query we handle. By default, caching is disabled unless specifying a value for `$cacheFor`. As long as `$cacheFor` is existent and is greater than `0`, all queries will be cached.

It is also possible to enable caching for specific queries. This is the recommended way because it is easier to manage each query.

```php
$postsCount = Post::cacheFor(60 * 60)->count();

// Using a DateTime instance like Carbon works perfectly fine!
$postsCount = Post::cacheFor(now()->addDays(1))->count();
```

## Cache Tags & Cache Invalidation
Some caching stores accept tags. This is really useful if you plan on tagging your cached queries and invalidate only some of the queries when needed.

```php
$shelfOneBooks = Book::whereShelf(1)->cacheFor(60)->cacheTags(['shelf:1'])->get();
$shelfTwoBooks = Book::whereShelf(2)->cacheFor(60)->cacheTags(['shelf:2'])->get();

// After flushing the cache for shelf:1, the query of$shelfTwoBooks will still hit the cache if re-called again.
Book::flushQueryCache(['shelf:1']);

// Flushing also works for both tags, invalidating them both, not just the one tagged with shelf:1
Book::flushQueryCache(['shelf:1', 'shelf:2']);
```

Be careful tho - specifying cache tags does not change the behaviour of key storage.
For example, the following two queries, altough the use the same tag, they have different keys stored in the caching database.

```php
$alice = Kid::whereName('Alice')->cacheFor(60)->cacheTags(['kids'])->first();
$bob = Kid::whereName('Bob')->cacheFor(60)->cacheTags(['kids'])->first();
```

## Relationship Caching
Relationships are just another queries. They can be intercepted and modified before the database is hit with the query. The following example needs the `Order` model (or the model associated with the `orders` relationship) to include the `QueryCacheable` trait.

```php
$user = User::with(['orders' => function ($query) {
    return $query->cacheFor(60 * 60)->cacheTags(['my:orders']);
}])->get();

// This comes from the cache if existed.
$orders = $user->orders;
```

## Cache Keys
The package automatically generate the keys needed to store the data in the cache store. However, prefixing them might be useful if the cache store is used by other applications and/or models and you want to manage the keys better to avoid collisions.

```php
$bob = Kid::whereName('Bob')->cacheFor(60)->cachePrefix('kids_')->first();
```

If no prefix is specified, the string `leqc` is going to be used.

## Cache Drivers
By default, the trait uses the default cache driver. If you want to **force** a specific one, you can do so by calling `cacheDriver()`:

```php
$bob = Kid::whereName('Bob')->cacheFor(60)->cacheDriver('dynamodb')->first();
```

## Disable caching
If you enabled caching (either by model variable or by the `cacheFor` scope), you can also opt to disable it mid-builder.
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
