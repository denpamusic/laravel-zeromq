<?php

namespace Denpa\ZeroMQ;

use Illuminate\Broadcasting\Broadcasters\Broadcaster as IlluminateBroadcaster;

class Broadcaster extends IlluminateBroadcaster
{
    /**
     * ZeroMQ manager.
     *
     * @var \Denpa\ZeroMQ\Manager
     */
    protected $zeromq;

    /**
     * ZeroMQ connection.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * Constructs broadcaster instance.
     *
     * @param  \Denpa\ZeroMQ\Manager  $manager
     * @param  string|null  $connection
     *
     * @return void
     */
    public function __construct(Manager $zeromq, $connection = null)
    {
        $this->zeromq = $zeromq;
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
        $connection = $this->zeromq->connection($this->connection);

        foreach ($this->formatChannels($channels) as $channel) {
            $connection->publish($channel, $payload);
        }
    }
}
