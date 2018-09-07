<?php

namespace Denpa\ZeroMQ;

use React\ZMQ\Context;
use React\EventLoop\Factory as EventLoop;

class Connector
{
    /**
     * Creates new ZeroMQ context.
     *
     * @param array $config
     *
     * @return \Denpa\ZeroMQ\Connection
     */
    public function context(array $config)
    {
        $loop = EventLoop::create();

        return new Connection(new Context($loop), $loop, $config);
    }
}
