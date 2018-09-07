<?php

use Denpa\ZeroMQ\Manager;
use Denpa\ZeroMQ\Connection;
use React\EventLoop\LoopInterface;

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
        $loop = zeromq()->getLoop();

        $this->assertInstanceOf(LoopInterface::class, $loop);
    }
}
