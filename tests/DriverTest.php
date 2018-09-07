<?php

use Denpa\ZeroMQ\Broadcaster;

class DriverTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        config()->set('broadcasting.default', 'zeromq');
        config()->set('broadcasting.connections.zeromq.driver', 'zeromq');
    }

    /**
     * Test broadcast action.
     *
     * @return void
     */
    public function testBroadcast()
    {
        $loop = $this->mockLoop();
        $socket = $this->mockSocket();
        $context = $this->mockContext();

        $socket
            ->expects($this->exactly(2))
            ->method('bind')
            ->willReturn($socket);

        $socket
            ->expects($this->exactly(2))
            ->method('sendmulti')
            ->withConsecutive(
                [['foo', json_encode(['zab' => 'baz'])]],
                [['bar', json_encode(['zab' => 'baz'])]]
            );

        $context
            ->expects($this->exactly(2))
            ->method('__call')
            ->with('getSocket', [ZMQ::SOCKET_PUB])
            ->willReturn($socket);

        zeromq()
            ->setLoop($loop)
            ->setContext($context);

        $driver = $this->app['Illuminate\Contracts\Broadcasting\Factory']
            ->driver();

        $this->assertInstanceOf(Broadcaster::class, $driver);
        $driver->broadcast(['foo', 'bar'], 'test', ['zab' => 'baz']);
    }
}
