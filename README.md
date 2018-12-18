# ZeroMQ driver for Laravel

[![Latest Stable Version](https://poser.pugx.org/denpa/laravel-zeromq/v/stable)](https://packagist.org/packages/denpa/laravel-zeromq)
[![License](https://poser.pugx.org/denpa/laravel-zeromq/license)](https://packagist.org/packages/denpa/laravel-zeromq)
[![Build Status](https://travis-ci.org/denpamusic/laravel-zeromq.svg)](https://travis-ci.org/denpamusic/laravel-zeromq)
[![Code Climate](https://codeclimate.com/github/denpamusic/laravel-zeromq/badges/gpa.svg)](https://codeclimate.com/github/denpamusic/laravel-zeromq)
[![Code Coverage](https://codeclimate.com/github/denpamusic/laravel-zeromq/badges/coverage.svg)](https://codeclimate.com/github/denpamusic/laravel-zeromq/coverage)

## About
Fully unit-tested Laravel ZeroMQ driver based on react/zmq.

## Installation
1. Install package:
```sh
composer require denpa/laravel-zeromq "^1.0"
```

2. _(skip if using Laravel 5.5 or newer)_ Add service provider and facade to ./config/app.php
```php
'providers' => [
    ...
    Denpa\ZeroMQ\Providers\ServiceProvider::class,
];
```

```php
'aliases' => [
    ...
    'ZeroMQ' => Denpa\ZeroMQ\Facades\ZeroMQ::class,
];
```

3. Publish config file
`php artisan vendor:publish --provider="Denpa\ZeroMQ\Providers\ServiceProvider"` in your project directory.

## Requirements
* PHP 7.1 or higher
* ZMQ PHP extension
* Laravel 5.2 or higher

## Usage
Publish:
```php
zeromq()->publish(['foo', 'bar'], 'hello');
zeromq()->connection('test')->publish(['foo', 'bar'], 'hello');
```

Pull:
```php
zeromq()->pull(function ($message) {
    echo $message;
});
```

Push:
```php
zeromq()->push('hello');
```

Subscribe:
```php
zeromq()->subscribe(['foo', 'bar'], function ($message) {
    echo $message;
});
```
## Facade
```php
use Denpa\ZeroMQ\Facades\ZeroMQ;

$callback = function ($message) {
    echo $message;
};

// use default connection
ZeroMQ::publish(['foo', 'bar'], 'hello');
ZeroMQ::pull($callback);
ZeroMQ::push('hello');
ZeroMQ::subscribe(['foo', 'bar'], $callback);

// use different connection
ZeroMQ::connection('baz')->push('hello');
```

## Broadcasting
Set `BROADCAST_DRIVER=zeromq` in environment file and add following lines
```php
'zeromq' => [
    'driver' => 'zeromq',
],
```
to 'connections' key in `config/broadcasting.php`.

Now use laravel `broadcast($event);` helper to broadcast events via ZeroMQ.

## Bitcoin Core (laravel-bitcoinrpc)
[laravel-bitcoinrpc](https://github.com/denpamusic/laravel-bitcoinrpc) integrates this package to subscribe to topics broadcasted by Bitcoin Core (and some forks).
```php
bitcoind()->on('hashblock', function ($blockhash, $sequence) {
    // get hash of new best block and retrieve full block info
    $block = bitcoind()->getBlock($blockhash);
    print_r($block->get());
});
```
For more info, visit [laravel-bitcoinrpc documentation](https://laravel-bitcoinrpc.denpa.pro/docs/zeromq/).

## License
This product is distributed under [MIT license](https://github.com/denpamusic/laravel-zeromq/blob/master/LICENSE).
