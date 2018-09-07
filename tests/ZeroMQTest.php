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
                'protocol' => 'tcp',
                'host'     => 'localhost',
                'port'     => 8443,
            ],
        ]);
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
    public function testMagicCall()
    {
        $loop = zeromq()->getLoop();

        $this->assertInstanceOf(\React\EventLoop\LoopInterface::class, $loop);
    }

    public function testBroadcasterExtension()
    {
        config()->set('broadcasting.default', 'zeromq');
        config()->set('broadcasting.connections.zeromq.driver', 'zeromq');

        $this->assertInstanceOf(
            Broadcaster::class,
            $this->app['Illuminate\Contracts\Broadcasting\Factory']->driver()
        );
    }
}
