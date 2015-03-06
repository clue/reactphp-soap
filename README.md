# clue/soap-react [![Build Status](https://travis-ci.org/clue/php-soap-react.svg?branch=master)](https://travis-ci.org/clue/php-soap-react)

A simple, async [SOAP](http://en.wikipedia.org/wiki/SOAP) web service client library, built on top of [React PHP](http://reactphp.org/).

Most notably, SOAP is often used for invoking
[Remote procedure calls](http://en.wikipedia.org/wiki/Remote_procedure_call) (RPCs)
in distributed systems.
Internally, SOAP messages are encoded as XML and usually sent via HTTP POST requests.
For the most part, SOAP (originally *Simple Object Access protocol*) is a protocol of the past,
and in fact anything but *simple*.
It is still in use by many (often *legacy*) systems.

This project provides a *simple* API for invoking *async* RPCs to remote web services.

* **Async execution of functions** -
  Send any number of functions (RPCs) to the remote web service in parallel and
  process their responses as soon as results come in.
  The Promise-based design provides a *sane* interface to working with out of bound responses.
* **Async processing of the WSDL** -
  The WSDL (web service description language) file will be downloaded and processed
  in the background.
* **Event-driven core** -
  Internally, everything uses event handlers to react to incoming events, such as an incoming RPC result.
* **Lightweight, SOLID design** -
  Provides a thin abstraction that is [*just good enough*](http://en.wikipedia.org/wiki/Principle_of_good_enough)
  and does not get in your way.
  Built on top of tested components instead of re-inventing the wheel.
* **Good test coverage** -
  Comes with an automated tests suite and is regularly tested against actual web services in the wild

> Note: This project is in beta stage! Feel free to report any issues you encounter.

## Quickstart example

Once [installed](#install), you can use the following code to query an example
web service via SOAP:

```php
$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
$wsdl = 'http://example.com/demo.wsdl';

$factory->createClient($wsdl)->then(function (Client $client) {
    $api = new Proxy($client);

    $api->getBank(array('blz' => '12070000'))->then(function ($result) {
        var_dump('Result', $result);
    });
});

$loop->run();
```

See also the [examples](examples).

## Usage

### Factory

The `Factory` class is responsible for fetching the WSDL file once and constructing
the `Client` instance.
It also registers everything with the main [`EventLoop`](https://github.com/reactphp/event-loop#usage).

```php
$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
```

If you need custom DNS or proxy settings, you can explicitly pass a
custom [`Browser`](https://github.com/clue/php-buzz-react#browser) instance:

```php
$browser = new Clue\React\Buzz\Browser($loop);
$factory = new Factory($loop, $browser);
```

### Client

The `Client` class is responsible for communication with the remote SOAP
WebService server.

> Note: It's recommended (and easier) to wrap the `Client` in a `Proxy` instance
> (see below). The rest of this chapter is considered advanced usage.

The `soapCall($method, $arguments)` method can be used to queue the given
function to be sent via SOAP and wait for a response from the remote web service.

The `getFunctions()` method returns an array of functions defined in the WSDL.
It returns the equivalent of PHP's [`SoapClient::__getFunctions()`](http://php.net/manual/en/soapclient.getfunctions.php).

The `getTypes()` method returns an array of types defined in the WSDL.
It returns the equivalent of PHP's [`SoapClient::__getTypes()`](http://php.net/manual/en/soapclient.gettypes.php).


### Proxy

The `Proxy` class wraps an existing `Client` instance in order to ease calling
SOAP functions.

```php
$proxy = new Proxy($client);

$proxy->myMethod($myArg1, $myArg2)->then(function ($response) {
    // result received
});
```

Each and every method call will be forwarded to `Client::soapCall()`.

## Install

The recommended way to install this library is [through composer](packagist://getcomposer.org).
[New to composer?](packagist://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/soap-react": "~0.1.0"
    }
}
```

## License

MIT
