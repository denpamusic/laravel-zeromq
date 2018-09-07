<?php

if (! function_exists('zeromq')) {
    /**
     * Get ZeroMQ connection manager.
     *
     * @return \Denpa\ZeroMQ\Connections\Connection
     */
    function zeromq()
    {
        return app('zeromq');
    }
}
