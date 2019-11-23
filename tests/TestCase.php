<?php

namespace Rennokki\QueryCache\Test;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->resetDatabase();
        $this->clearCache();

        $this->loadLaravelMigrations(['--database' => 'sqlite']);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->withFactories(__DIR__.'/../database/factories');

        $this->artisan('migrate', ['--database' => 'sqlite']);
    }

    protected function getPackageProviders($app)
    {
        return [
            //
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => __DIR__.'/database.sqlite',
            'prefix'   => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('auth.providers.posts.model', Post::class);
        $app['config']->set('auth.providers.kids.model', Kid::class);
        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');
    }

    protected function resetDatabase()
    {
        file_put_contents(__DIR__.'/database.sqlite', null);
    }

    protected function clearCache()
    {
        $this->artisan('cache:clear');
    }
}
