<?php

use Denpa\ZeroMQ\Providers\ServiceProvider;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'ZeroMQ' => 'Denpa\ZeroMQ\Facades\ZeroMQ',
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('zeromq.connections', [
            'default' => [
                'protocol' => 'tcp',
                'host'     => 'localhost',
                'port'     => 8443,
            ],
        ]);
    }

    /**
     * Polyfill for asserting response status for laravel 5.2 or higher.
     *
     * @param  Response  $response
     * @param  int       $code
     *
     * @return void
     */
    protected function assertStatus($response, $code = 200)
    {
        if (method_exists($response, 'assertResponseStatus')) {
            return $response->assertResponseStatus($code);
        }

        return $response->assertStatus($code);
    }

    /**
     * Polyfill for asserting content for laravel 5.2 or higher.
     *
     * @param  Response  $response
     * @param  string    $content
     *
     * @return void
     */
    protected function assertSee($response, $content)
    {
        if (method_exists($response, 'see')) {
            return $response->see($content);
        }

        return $response->assertSee($content);
    }

    /**
     * Polyfill for asserting json content for laravel 5.2 or higher.
     *
     * @param  Response  $response
     * @param  array     $json
     *
     * @return void
     */
    protected function assertJsonEquals($response, array $json)
    {
        if (method_exists($response, 'seeJsonEquals')) {
            return $response->seeJsonEquals($json);
        }

        return $response->assertJson($json);
    }

    /**
     * Checks for laravel version.
     *
     * @param  mixed  $versions
     *
     * @return bool
     */
    protected function laravelVersion($versions)
    {
        return Str::startsWith($this->app::VERSION, $versions);
    }

    /**
     * Get Closure mock.
     *
     * @param  array  $with
     * @param  PHPUnit_Framework_MockObject_Matcher_InvokedCount|null  $expects
     * @param  PHPUnit_Framework_MockObject_Stub_Return|null  $return
     *
     * @return callable
     */
    protected function mockCallable(
        array $with = [],
        $expects = null,
        $return = null
    ) {
        $expects = $expects ?? $this->once();
        $return = $return ?? $this->returnValue(null);

        $callable = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($expects)
            ->method('__invoke')
            ->with(...$with)
            ->will($return);

        return $callable;
    }

    /**
     * Mock \React\EventLoop\LoopInterface.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockLoop()
    {
        return $this->getMockBuilder('React\EventLoop\LoopInterface')
            ->getMock();
    }

    /**
     * Mock \React\ZMQ\Context.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockContext()
    {
        return $this->getMockBuilder('React\ZMQ\Context')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock \React\ZMQ\SocketWrapper.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockSocket()
    {
        return $this->getMockBuilder('React\ZMQ\SocketWrapper')
            ->disableOriginalConstructor()
            ->setMethods([
                'bind',
                'connect',
                'subscribe',
                'send',
                'sendmulti',
                'on',
                'close',
                'end',
            ])
            ->getMock();
    }
}
