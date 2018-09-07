<?php

use Denpa\ZeroMQ\Manager;
use Denpa\ZeroMQ\Connection;
use React\ZMQ\SocketWrapper;
use React\EventLoop\LoopInterface;

class ConnectionTest extends TestCase
{
    /**
     * Test magic calls to context through connection.
     *
     * @return void
     */
    public function testMagicCall()
    {
        $socket = zeromq()->connection()->getSocket(ZMQ::SOCKET_PUSH);

        $this->assertInstanceOf(SocketWrapper::class, $socket);
    }

    /**
     * Test publish action.
     *
     * @return void
     */
    public function testPublish()
    {
        $loop = $this->mockLoop();
        $socket = $this->mockSocket();
        $context = $this->mockContext();

        $socket
            ->expects($this->once())
            ->method('bind')
            ->willReturn($socket);

        $socket
            ->expects($this->once())
            ->method('sendmulti')
            ->with(['test', json_encode(['foo' => 'bar'])]);

        $context
            ->expects($this->once())
            ->method('__call')
            ->with('getSocket', [ZMQ::SOCKET_PUB])
            ->willReturn($socket);

        zeromq()
            ->setLoop($loop)
            ->setContext($context)
            ->publish('test', ['foo' => 'bar']);
    }

    /**
     * Test pull action.
     *
     * @return void
     */
    public function testPull()
    {
        $loop = $this->mockLoop();
        $socket = $this->mockSocket();
        $context = $this->mockContext();
        $callback = $this->mockCallable([
            $this->callback(function ($message) {
                return $message == 'test';
            }),
        ]);

        $socket
            ->expects($this->once())
            ->method('bind')
            ->will($this->returnSelf());

        $socket
            ->expects($this->once())
            ->method('on')
            ->with('messages', $callback)
            ->will($this->returnValue($callback('test')));

        $context
            ->expects($this->once())
            ->method('__call')
            ->with('getSocket', [ZMQ::SOCKET_PULL])
            ->willReturn($socket);

        $loop
            ->expects($this->once())
            ->method('run');

        zeromq()
            ->setLoop($loop)
            ->setContext($context)
            ->pull($callback);
    }

    /**
     * Test push action.
     *
     * @return void
     */
    public function testPush()
    {
        $loop = $this->mockLoop();
        $socket = $this->mockSocket();
        $context = $this->mockContext();

        $socket
            ->expects($this->once())
            ->method('connect')
            ->will($this->returnSelf());

        $socket
            ->method('send')
            ->with('test');

        $context
            ->expects($this->once())
            ->method('__call')
            ->with('getSocket', [ZMQ::SOCKET_PUSH])
            ->willReturn($socket);

        zeromq()
            ->setLoop($loop)
            ->setContext($context)
            ->push('test');
    }

    /**
     * Test subscribe action.
     *
     * @return void
     */
    public function testSubscribe()
    {
        $loop = $this->mockLoop();
        $socket = $this->mockSocket();
        $context = $this->mockContext();
        $callback = $this->mockCallable([
            $this->callback(function ($message) {
                return $message == 'test';
            }),
        ]);

        $context
            ->expects($this->once())
            ->method('__call')
            ->with('getSocket', [ZMQ::SOCKET_SUB])
            ->willReturn($socket);

        $socket
            ->expects($this->once())
            ->method('connect')
            ->will($this->returnSelf());

        $socket
            ->expects($this->exactly(2))
            ->method('subscribe')
            ->withConsecutive(
                [$this->equalTo('foo')],
                [$this->equalTo('bar')]
            );

        $socket
            ->expects($this->once())
            ->method('on')
            ->with('messages', $callback)
            ->will($this->returnValue($callback('test')));

        $loop
            ->expects($this->once())
            ->method('run');

        zeromq()
            ->setLoop($loop)
            ->setContext($context)
            ->subscribe(['foo', 'bar'], $callback);
    }
}
