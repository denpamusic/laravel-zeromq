<?php

declare(strict_types=1);

namespace Denpa\ZeroMQ\Providers;

use Denpa\ZeroMQ\Broadcaster;
use Denpa\ZeroMQ\Manager;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $path = realpath(__DIR__.'/../../config/config.php');

        $this->publishes([$path => config_path('zeromq.php')], 'config');
        $this->mergeConfigFrom($path, 'zeromq');

        $this->app->make('Illuminate\Contracts\Broadcasting\Factory')
            ->extend('zeromq', function ($app) {
                return new Broadcaster($app['zeromq']);
            });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerAliases();

        $this->registerManager();
        $this->registerConnection();
    }

    /**
     * Register aliases.
     *
     * @return void
     */
    protected function registerAliases(): void
    {
        $aliases = [
            'zeromq'            => 'Denpa\ZeroMQ\Manager',
            'zeromq.connection' => 'Denpa\ZeroMQ\Connection',
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->app->alias($key, $alias);
            }
        }
    }

    /**
     * Register connection manager.
     *
     * @return void
     */
    public function registerManager(): void
    {
        $this->app->singleton('zeromq', function ($app) {
            return new Manager(config('zeromq.connections'));
        });
    }

    /**
     * Registers connection.
     *
     * @return void
     */
    public function registerConnection(): void
    {
        $this->app->bind('zeromq.connection', function ($app) {
            return $app['zeromq']->connection();
        });
    }
}
