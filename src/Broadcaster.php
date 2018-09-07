<?php

namespace Denpa\ZeroMQ;

use Illuminate\Broadcasting\Broadcasters\Broadcaster as IlluminateBroadcaster;

class Broadcaster extends IlluminateBroadcaster
{
    /**
     * Default ZeroMQ connection.
     *
     * @var \Denpa\ZeroMQ\Connection
     */
    protected $connection;

    /**
     * Constructs broadcaster instance.
     *
     * @param  \Denpa\ZeroMQ\Connection  $connection
     *
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function auth($request)
    {
        //
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     *
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        //
    }

    /**
     * Broadcasts payload to channels.
     *
     * @param  array   $channels
     * @param  string  $event
     * @param  array   $payload
     *
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->connection->publish($channels, $payload);
    }
}
