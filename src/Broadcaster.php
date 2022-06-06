<?php

declare(strict_types=1);

namespace Denpa\ZeroMQ;

use Denpa\ZeroMQ\Manager as ZeroMQ;
use Illuminate\Broadcasting\Broadcasters\Broadcaster as IlluminateBroadcaster;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
     * @return void
     */
    public function __construct(ZeroMQ $zeromq, ?string $connection = null)
    {
        $this->zeromq = $zeromq;
        $this->connection = $connection;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
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
     * @return string
     */
    public function validAuthenticationResponse($request, $result): string
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
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        $connection = $this->zeromq->connection($this->connection);

        $payload = json_encode([
            'event'  => $event,
            'data'   => $payload,
            'socket' => Arr::pull($payload, 'socket'),
        ]);

        $connection->publish($this->formatChannels($channels), $payload)->run();
    }
}
