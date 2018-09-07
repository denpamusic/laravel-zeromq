<?php

use Orchestra\Testbench\TestCase;

use Denpa\ZeroMQ\Manager;
use Denpa\ZeroMQ\Connection;

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
}
