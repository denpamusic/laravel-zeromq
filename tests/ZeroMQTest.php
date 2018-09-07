<?php

use Denpa\ZeroMQ\Manager;
use Denpa\ZeroMQ\Connection;
use Denpa\ZeroMQ\Broadcaster;
use Orchestra\Testbench\TestCase;

class ZeroMQTest extends TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Denpa\ZeroMQ\Providers\ServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'ZeroMQ' => 'Denpa\ZeroMQ\Facades\ZeroMQ',
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('zeromq.connections', [
            'default' => [
                'protocol' => 'ipc',
                'host'     => 'test.ipc',
                'port'     => 8443,
            ],
        ]);
    }

    /**
     * Get Closure mock.
     *
     * @param array $with
     *
     * @return callable
     */
    protected function mockCallable(array $with = [])
    {
        $callable = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->once())
            ->method('__invoke')
            ->with(...$with);

        return $callable;
    }

    protected function mockLoop()
    {
        return $this->getMockBuilder('React\EventLoop\LoopInterface')
            ->getMock();
    }

    protected function mockContext()
    {
        return $this->getMockBuilder('React\ZMQ\Context')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function mockSocket()
    {
        return $this->getMockBuilder('ZMQSocket')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function mockEmitter()
    {
        return $this->getMockBuilder('Evenement\EventEmitter')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test service provider.
     *
     * @return void
     */
    public function testServiceIsAvailable()
    {
        $this->assertTrue($this->app->bound('zeromq'));
        $this->assertTrue($this->app->bound('zeromq.connection'));
        $this->assertInstanceOf(Manager::class, $this->app['zeromq']);
        $this->assertInstanceOf(Connection::class, $this->app['zeromq.connection']);
    }

    /**
     * Test facade.
     *
     * @return void
     */
    public function testFacade()
    {
        $this->assertInstanceOf(Manager::class, \ZeroMQ::getFacadeRoot());
        $this->assertInstanceOf(Connection::class, \ZeroMQ::getFacadeRoot()->connection());
    }

    /**
     * Test helper.
     *
     * @return void
     */
    public function testHelper()
    {
        $this->assertInstanceOf(Manager::class, zeromq());
        $this->assertInstanceOf(Connection::class, zeromq()->connection());
    }

    /**
     * Test nonexistent configuration.
     *
     * @return void
     */
    public function testNonexistentConfiguration()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not find connection configuration [nonexistent]');

        zeromq()->connection('nonexistent')->getLoop();
    }

    /**
     * Test magic calls to default connection through manager.
     *
     * @return void
     */
    public function testMagicCallManager()
    {
        $loop = zeromq()->getLoop();

        $this->assertInstanceOf(\React\EventLoop\LoopInterface::class, $loop);
    }

    /**
     * Test magic calls to context through connection.
     *
     * @return void
     */
    public function testMagicCallContext()
    {
        $socket = zeromq()->connection()->getSocket(ZMQ::SOCKET_PUSH);

        $this->assertInstanceOf(\React\ZMQ\SocketWrapper::class, $socket);
    }

    /**
     * Test laravel broadcaster extension.
     *
     * @return void
     */
    public function testBroadcasterExtension()
    {
        config()->set('broadcasting.default', 'zeromq');
        config()->set('broadcasting.connections.zeromq.driver', 'zeromq');

        $driver = $this->app['Illuminate\Contracts\Broadcasting\Factory']
            ->driver();

        $this->assertInstanceOf(Broadcaster::class, $driver);
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
        $emitter = $this->mockEmitter();
        $callback = $this->mockCallable([
            $this->callback(function($message) {
                return $message == 'test';
            }),
        ]);

        $emitter
            ->expects($this->once())
            ->method('on')
            ->with('messages', $callback)
            ->will($this->returnValue($callback('test')));

        $socket
            ->expects($this->once())
            ->method('bind')
            ->willReturn($emitter);

        $context
            ->expects($this->once())
            ->method('__call')
            ->with('getSocket', [ZMQ::SOCKET_PULL])
            ->willReturn($socket);

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
            ->willReturn($socket);

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
}
