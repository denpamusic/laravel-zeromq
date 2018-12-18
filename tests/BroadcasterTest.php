<?php

use Denpa\ZeroMQ\Broadcaster;
use Illuminate\Foundation\Auth\User as Authenticatable;

class BroadcasterTest extends TestCase
{
    /**
     * Set-up test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $appkey = base64_encode(md5(uniqid(rand(), true)));
        $this->app['config']->set('app.key', "base64:$appkey");
        $this->app['config']->set('broadcasting.default', 'zeromq');
        $this->app['config']
            ->set('broadcasting.connections.zeromq.driver', 'zeromq');

        $broadcast = $this->app['Illuminate\Broadcasting\BroadcastManager'];
        $broadcast->routes();
        $broadcast->channel('test1.{user_id}', function ($user, $user_id) {
            return $user->id == $user_id;
        });
        $broadcast->channel('test2.{user_id}', function ($user, $user_id) {
            return ['foo' => 'bar'];
        });
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
        $this->post('/broadcasting/auth', ['channel_name' => 'private-test1.1'])
            ->assertStatus(403);
    }

    /**
     * Test auth with boolean response.
     *
     * @return void
     */
    public function testAuthWithBoolResponse()
    {
        $this->actingAs(new FakeUser())
            ->post('/broadcasting/auth', ['channel_name' => 'private-test1.1'])
            ->assertStatus(200)
            ->assertSee('true');
    }

    /**
     * Test auth with json response.
     *
     * @return void
     */
    public function testAuthWithJsonResponse()
    {
        $this->actingAs(new FakeUser())
            ->post('/broadcasting/auth', ['channel_name' => 'private-test2.1'])
            ->assertStatus(200)
            ->assertJson(['channel_data' => [
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
