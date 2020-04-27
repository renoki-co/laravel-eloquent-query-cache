<?php
/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

use Illuminate\Support\Str;

$factory->define(\Rennokki\QueryCache\Test\Models\Page::class, function () {
    return [
        'name' => 'Page'.Str::random(5),
    ];
});
