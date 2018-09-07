<?php

namespace Denpa\ZeroMQ\Providers;

use Denpa\ZeroMQ\Manager;
use Denpa\ZeroMQ\Broadcaster;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../../config/config.php');

        $this->publishes([$path => config_path('zeromq.php')], 'config');
        $this->mergeConfigFrom($path, 'zeromq');

        $this->app->make('Illuminate\Contracts\Broadcasting\Factory')
            ->extend('zmq', function ($app) {
                return new Broadcaster($app['zeromq.connection']);
            });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
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
    protected function registerAliases()
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
    public function registerManager()
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
    public function registerConnection()
    {
        $this->app->bind('zeromq.connection', function ($app) {
            return $app['zeromq']->connection();
        });
    }
}
