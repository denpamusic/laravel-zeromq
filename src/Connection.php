<?php

namespace Denpa\ZeroMQ;

use ZMQ;
use React\ZMQ\Context;
use React\EventLoop\LoopInterface;

class Connection
{
    /**
     * ZMQ Context instance.
     *
     * @var \React\ZMQ\Context
     */
    protected $context;

    /**
     * Event loop instance.
     *
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * Constructs new ZMQ connection.
     *
     * @param  \React\ZMQ\Context              $context
     * @param  \React\EventLoop\LoopInterface  $loop
     * @param  array                           $config
     *
     * @return void
     */
    public function __construct(
        Context $context,
        LoopInterface $loop,
        array $config)
    {
        $this->loop = $loop;
        $this->context = $context;
        $this->config = $config;
    }

    /**
     * Proxies call to the context.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters = [])
    {
        return $this->context->{$method}(...$parameters);
    }

    /**
     * Gets loop interface.
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * Publishes message.
     *
     * @param  mixed  $message
     *
     * @return void
     */
    public function publish($message)
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_PUB);
        $socket->bind($this->getDsn())->send($message);

        $this->loop->run();
    }

    /**
     * Pulls message.
     *
     * @param  callable  $callback
     *
     * @return void
     */
    public function pull(callable $callback)
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_PULL);
        $socket->bind($this->getDsn())->on('messages', $callback);

        $this->loop->run();
    }

    /**
     * Pushes message.
     *
     * @return void
     */
    public function push($message)
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_PUSH);
        $socket->connect($this->getDsn())->send($message);

        $this->loop->run();
    }

    /**
     * Subscribes to channels.
     *
     * @param  array     $channels
     * @param  callable  $callback
     *
     * @return void
     */
    public function subscribe(array $channels, callable $callback)
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_SUB);

        $socket->connect($this->getDsn());

        foreach ($channels as $channel) {
            $socket->subscribe($channel);
        }

        $socket->on('messages', $callback);

        $this->loop->run();
    }

    /**
     * Gets DSN.
     *
     * @return string
     */
    protected function getDsn()
    {
        $protocol = $this->config['protocol'] ?? 'tcp';

        return $protocol.'://'.
            $this->config['host'].':'.
            $this->config['port'];
    }
}
