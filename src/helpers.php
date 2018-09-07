<?php

if (! function_exists('zeromq')) {
    /**
     * Get ZeroMQ connection by name.
     *
     * @param  string  $name
     *
     * @return \Denpa\ZeroMQ\Connections\Connection
     */
    function zeromq($name = 'default')
    {
        return app('zeromq')->get($name);
    }
}
