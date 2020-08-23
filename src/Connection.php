<?php

declare(strict_types=1);

namespace Denpa\ZeroMQ;

use React\EventLoop\LoopInterface;
use React\ZMQ\Context;
use React\ZMQ\SocketWrapper as Socket;
use ZMQ;

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
     * Sets ZeroMQ context.
     *
     * @param  \React\ZMQ\Context  $context
     *
     * @return self
     */
    public function setContext(Context $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Gets ZeroMQ context.
     *
     * @return \React\ZMQ\Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * Publishes message.
     *
     * @param  array  $channels
     * @param  mixed  $message
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function publish(array $channels, $message): LoopInterface
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_PUB);

        $socket->bind($this->getDsn());

        foreach ($channels as $channel) {
            $socket->sendmulti([$channel, $this->formatMessage($message)]);
        }

        $socket->end();

        return $this->loop;
    }

    /**
     * Pulls message.
     *
     * @param  callable  $callback
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function pull(callable $callback): LoopInterface
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
    public function push($message): LoopInterface
    {
        $socket = $this->context->getSocket(ZMQ::SOCKET_PUSH);

        $socket->connect($this->getDsn());
        $socket->send($this->formatMessage($message));

        $socket->end();

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
    public function subscribe(array $channels, callable $callback): LoopInterface
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
     * @param  \React\ZMQ\SocketWrapper|null $socket
     *
     * @return void
     */
    protected function onSuccess(string $message, callable $callback, ?Socket $socket): void
    {
        if ($callback($message) === false) {
            if (! $socket->closed) {
                $socket->close();
            }
        }
    }

    /**
     * Formats message.
     *
     * @param  mixed  $message
     *
     * @return string
     */
    protected function formatMessage($message): string
    {
        return is_array($message) || is_object($message) ?
            json_encode($message) : $message;
    }

    /**
     * Gets DSN.
     *
     * @return string
     */
    protected function getDsn(): string
    {
        $protocol = $this->config['protocol'] ?? 'tcp';

        return $protocol.'://'.
            $this->config['host'].':'.
            $this->config['port'];
    }
}
