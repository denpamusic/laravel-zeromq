<?php

declare(strict_types=1);

use Denpa\ZeroMQ\Manager;

if (! function_exists('zeromq')) {
    /**
     * Get ZeroMQ connection manager.
     *
     * @return \Denpa\ZeroMQ\Manager
     */
    function zeromq() : Manager
    {
        return app('zeromq');
    }
}
