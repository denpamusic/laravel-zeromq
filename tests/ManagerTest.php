<?php

use Denpa\ZeroMQ\Connection;
use Denpa\ZeroMQ\Manager;
use React\ZMQ\Context;

class ManagerTest extends TestCase
{
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
        $context = zeromq()->getContext();

        $this->assertInstanceOf(Context::class, $context);
    }

    /**
     * Test event loop run method.
     *
     * @return void
     */
    public function testRun()
    {
        $loop = $this->mockLoop();

        $loop
            ->expects($this->once())
            ->method('run');

        zeromq()->setLoop($loop);

        zeromq()->run();
    }

    /**
     * Test event loop stop method.
     *
     * @return void
     */
    public function testStop()
    {
        $loop = $this->mockLoop();

        $loop
            ->expects($this->once())
            ->method('stop');

        // loop should not start on destruct if it has been stopped
        $loop
            ->expects($this->never())
            ->method('run');

        zeromq()->setLoop($loop);

        zeromq()->stop();
        zeromq()->__destruct();
    }
}
