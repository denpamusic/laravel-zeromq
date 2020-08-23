<?php

use Denpa\ZeroMQ\Connection;
use Denpa\ZeroMQ\Manager;

class ProviderTest extends TestCase
{
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
}
