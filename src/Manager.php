<?php

namespace Denpa\ZeroMQ;

use InvalidArgumentException;

class Manager
{
    /**
     * ZeroMQ configurations.
     *
     * @var array
     */
    protected $config;

    /**
     * ZeroMQ connections.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $connections;

    /**
     * Creates new ZeroMQ manager instance.
     *
     * @param array $config
     *
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connections = collect();
    }

    /**
     * Gets ZeroMQ connection by name.
     *
     * @param  string|null  $name
     *
     * @return \Denpa\ZeroMQ\Connection
     */
    public function connection($name = null)
    {
        $name = $name ?: 'default';

        if (! $this->connections->has($name)) {
            $this->connections->put($name, $this->resolve($name));
        }

        return $this->connections->get($name);
    }

    /**
     * Resolve the given connection by name.
     *
     * @param string|null $name
     *
     * @return \Denpa\ZeroMQ\Connection
     */
    public function resolve($name = null)
    {
        $name = $name ?: 'default';

        if (! array_key_exists($name, $this->config)) {
            throw new InvalidArgumentException(
                "Could not find connection configuration [$name]"
            );
        }

        return $this->connector()->context($this->config[$name]);
    }

    /**
     * Gets connector.
     *
     * @param string|null $name
     *
     * @return \Denpa\ZeroMQ\Connector
     */
    public function connector($name = null)
    {
        $name = $name ?: 'default';

        return new Connector($this->config[$name]);
    }

    /**
     * Pass methods onto the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
