<?php

use Denpa\ZeroMQ\Broadcaster;
use Illuminate\Foundation\Auth\User as Authenticatable;

class BroadcasterTest extends TestCase
{
    /**
     * Makes test channel name.
     *
     * @param  string  $name
     * @param  string  $args,...
     * @return string
     */
    protected function makeChannelName($name, ...$args)
    {
        if ($this->laravelVersion('5.3')) {
            $callback = function ($arg) {
                return '*';
            };
        } else {
            $callback = function ($arg) {
                return "{{$arg}}";
            };
        }

        return "$name.".implode('.', array_map($callback, $args));
    }

    /**
     * Set-up test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $appkey = base64_encode(md5(uniqid(rand(), true)));
        $this->app['config']->set('app.key', "base64:$appkey");
        $this->app['config']->set('broadcasting.default', 'zeromq');
        $this->app['config']
            ->set('broadcasting.connections.zeromq.driver', 'zeromq');

        $broadcast = $this->app['Illuminate\Broadcasting\BroadcastManager'];
        $broadcast->routes();
        $broadcast->channel($this->makeChannelName('test1', 'user_id'),
            function ($user, $user_id) {
                return $user->id == $user_id;
            }
        );
        $broadcast->channel($this->makeChannelName('test2', 'user_id'),
            function ($user, $user_id) {
                return ['foo' => 'bar'];
            }
        );
    }

    /**
     * Test broadcast action.
     *
     * @return void
     */
    public function testBroadcast()
    {
        $loop = $this->mockLoop();
        $socket = $this->mockSocket();
        $context = $this->mockContext();

        $socket
            ->expects($this->once())
            ->method('bind')
            ->willReturn($socket);

        $socket
            ->expects($this->exactly(2))
            ->method('sendmulti')
            ->withConsecutive(
                [[
                    'foo',
                    json_encode([
                        'event'  => 'test',
                        'data'   => ['zab' => 'baz'],
                        'socket' => null,
                    ]),
                ]],
                [[
                    'bar',
                    json_encode([
                        'event'  => 'test',
                        'data'   => ['zab' => 'baz'],
                        'socket' => null,
                    ]),
                ]]
            );

        $context
            ->expects($this->once())
            ->method('__call')
            ->with('getSocket', [ZMQ::SOCKET_PUB])
            ->willReturn($socket);

        zeromq()
            ->setLoop($loop)
            ->setContext($context);

        $driver = $this->app['Illuminate\Contracts\Broadcasting\Factory']
            ->driver();

        $this->assertInstanceOf(Broadcaster::class, $driver);
        $driver->broadcast(['foo', 'bar'], 'test', ['zab' => 'baz']);
    }

    /**
     * Test auth with Forbidden response.
     *
     * @return void
     */
    public function testAuthForbidden()
    {
        $response = $this->post('/broadcasting/auth', ['channel_name' => 'private-test1.1']);
        $this->assertStatus($response, 403);
    }

    /**
     * Test auth with boolean response.
     *
     * @return void
     */
    public function testAuthWithBoolResponse()
    {
        $response = $this->actingAs(new FakeUser())
            ->post('/broadcasting/auth', ['channel_name' => 'private-test1.1']);

        $this->assertStatus($response, 200);
        $this->assertSee($response, 'true');
    }

    /**
     * Test auth with json response.
     *
     * @return void
     */
    public function testAuthWithJsonResponse()
    {
        $response = $this->actingAs(new FakeUser())
            ->post('/broadcasting/auth', ['channel_name' => 'private-test2.1']);

        $this->assertStatus($response, 200);
        $this->assertJsonEquals($response, ['channel_data' => [
            'user_id' => 1,
            'user_info' => ['foo' => 'bar'],
        ]]);
    }
}

class FakeUser extends Authenticatable
{
    public $attributes = [
        'id'    => 1,
        'name'  => 'Denis Paavilainen',
        'email' => 'denpa@denpa.pro',
    ];
}
