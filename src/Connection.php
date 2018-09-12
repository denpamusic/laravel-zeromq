<?php

namespace Denpa\ZeroMQ;

use ZMQ;
use React\ZMQ\Context;
use React\ZMQ\SocketWrapper as Socket;
use React\EventLoop\LoopInterface as EventLoop;

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
        EventLoop $loop,
        array $config
    ) {
        $this->context = $context;
        $this->loop = $loop;
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
     * Sets loop interface.
     *
     * @return static
     */
    public function setContext(Context $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Publishes message.
     *
     * @param  array  $channels
     * @param  mixed  $message
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function publish(array $channels, $message)
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_PUB);

        $socket->bind($this->getDsn());

        foreach ($channels as $channel) {
            $socket->sendmulti([$channel, $this->formatMessage($message)]);
        }

        $socket->close();

        return $this->loop;
    }

    /**
     * Pulls message.
     *
     * @param  callable  $callback
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function pull(callable $callback)
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_PULL);
        $socket->bind($this->getDsn());

        $onSuccess = function ($message) use ($callback, $socket) {
            $this->onSuccess($message, $callback, $socket);
        };

        $socket->on('messages', $onSuccess);
        $socket->on('message', $onSuccess);

        return $this->loop;
    }

    /**
     * Pushes message.
     *
     * @param  mixed  $message
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function push($message)
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_PUSH);

        $socket->connect($this->getDsn());
        $socket->send($this->formatMessage($message));

        $socket->close();

        return $this->loop;
    }

    /**
     * Subscribes to channels.
     *
     * @param  array     $channels
     * @param  callable  $callback
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function subscribe(array $channels, callable $callback)
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_SUB);

        $socket->connect($this->getDsn());

        foreach ($channels as $channel) {
            $socket->subscribe($channel);
        }

        $onSuccess = function ($message) use ($callback, $socket) {
            $this->onSuccess($message, $callback, $socket);
        };

        $socket->on('messages', $onSuccess);
        $socket->on('message', $onSuccess);

        return $this->loop;
    }

    /**
     * Success callback.
     *
     * @param  string    $message
     * @param  callable  $callback
     * @param  \React\ZMQ\SocketWrapper $socket
     *
     * @return void
     */
    protected function onSuccess($message, callable $callback, Socket $socket)
    {
        if ($callback($message) === false) {
            $socket->close();
        }
    }

    /**
     * Formats message.
     *
     * @param  mixed  $message
     *
     * @return string
     */
    protected function formatMessage($message)
    {
        return is_array($message) || is_object($message) ?
            json_encode($message) : $message;
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
