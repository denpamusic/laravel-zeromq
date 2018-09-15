<?php

use Denpa\ZeroMQ\Providers\ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
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
            ServiceProvider::class,
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
                'protocol' => 'tcp',
                'host'     => 'localhost',
                'port'     => 8443,
            ],
        ]);
    }

    /**
     * Get Closure mock.
     *
     * @param  array  $with
     * @param  PHPUnit_Framework_MockObject_Matcher_InvokedCount|null  $expects
     * @param  PHPUnit_Framework_MockObject_Stub_Return|null  $return
     *
     * @return callable
     */
    protected function mockCallable(
        array $with = [],
        $expects = null,
        $return = null
    ) {
        $expects = $expects ?? $this->once();
        $return = $return ?? $this->returnValue(null);

        $callable = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($expects)
            ->method('__invoke')
            ->with(...$with)
            ->will($return);

        return $callable;
    }

    /**
     * Mock \React\EventLoop\LoopInterface.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockLoop()
    {
        return $this->getMockBuilder('React\EventLoop\LoopInterface')
            ->getMock();
    }

    /**
     * Mock \React\ZMQ\Context.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockContext()
    {
        return $this->getMockBuilder('React\ZMQ\Context')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock \React\ZMQ\SocketWrapper.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockSocket()
    {
        return $this->getMockBuilder('React\ZMQ\SocketWrapper')
            ->disableOriginalConstructor()
            ->setMethods([
                'bind',
                'connect',
                'subscribe',
                'send',
                'sendmulti',
                'on',
                'close',
                'end',
            ])
            ->getMock();
    }
}
