<?php

namespace Denpa\ZeroMQ;

use Illuminate\Support\Str;
use Denpa\ZeroMQ\Manager as ZeroMQ;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
    public function __construct(ZeroMQ $zeromq, $connection = null)
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
        if (Str::startsWith($request->channel_name, ['private-', 'presence-']) &&
            ! $request->user()) {
            throw new AccessDeniedHttpException;
        }

        $channelName = Str::startsWith($request->channel_name, 'private-')
                            ? Str::replaceFirst('private-', '', $request->channel_name)
                            : Str::replaceFirst('presence-', '', $request->channel_name);

        return parent::verifyUserCanAccessChannel(
            $request, $channelName
        );
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
        if (is_bool($result)) {
            return json_encode($result);
        }

        return json_encode(['channel_data' => [
            'user_id' => $request->user()->getAuthIdentifier(),
            'user_info' => $result,
        ]]);
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

        $payload = json_encode([
            'event'  => $event,
            'data'   => $payload,
            'socket' => array_pull($payload, 'socket'),
        ]);

        $connection->publish($this->formatChannels($channels), $payload)->run();
    }
}
