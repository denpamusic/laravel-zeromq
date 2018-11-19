<?php

declare(strict_types=1);

namespace Denpa\ZeroMQ;

use React\ZMQ\Context;
use Denpa\ZeroMQ\Connection;
use InvalidArgumentException;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as EventLoop;

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
     * @var array
     */
    protected $connections = [];

    /**
     * Event loop instance.
     *
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * Was loop manually stopped.
     *
     * @var bool
     */
    protected $stopped = false;

    /**
     * Creates new ZeroMQ manager instance.
     *
     * @param  array  $config
     *
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->loop = EventLoop::create();
    }

    /**
     * Runs event loop on instance destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        if (! $this->stopped) {
            $this->loop->run();
        }
    }

    /**
     * Sets event loop.
     *
     * @param  \React\EventLoop\LoopInterface  $loop
     *
     * @return self
     */
    public function setLoop(LoopInterface $loop) : self
    {
        $this->loop = $loop;

        return $this;
    }

    /**
     * Gets ZeroMQ connection by name.
     *
     * @param  string|null  $name
     *
     * @return \Denpa\ZeroMQ\Connection
     */
    public function connection(?string $name = null) : Connection
    {
        $name = $name ?: 'default';

        if (! array_key_exists($name, $this->connections)) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    /**
     * Resolve the given connection by name.
     *
     * @param string|null $name
     *
     * @return \Denpa\ZeroMQ\Connection
     */
    public function resolve(?string $name = null) : Connection
    {
        $name = $name ?: 'default';

        if (! array_key_exists($name, $this->config)) {
            throw new InvalidArgumentException(
                "Could not find connection configuration [$name]"
            );
        }

        return $this->make($this->config[$name]);
    }

    /**
     * Creates new ZeroMQ connection.
     *
     * @param  array  $config
     *
     * @return \Denpa\ZeroMQ\Connection
     */
    public function make(array $config) : Connection
    {
        return new Connection(new Context($this->loop), $this->loop, $config);
    }

    /**
     * Runs event loop.
     *
     * @return void
     */
    public function run() : void
    {
        $this->loop->run();
    }

    /**
     * Stops event loop.
     *
     * @return void
     */
    public function stop() : void
    {
        $this->loop->stop();
        $this->stopped = true;
    }

    /**
     * Pass methods onto the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
