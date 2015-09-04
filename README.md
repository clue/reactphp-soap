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
the [`Client`](#client) instance.
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

#### createClient()

The `createClient($wsdl)` method can be used to download the WSDL at the
given URL into memory and create a new [`Client`](#client).

```php
$factory->createClient($url)->then(
    function (Client $client) {
        // client ready
    },
    function (Exception $e) {
        // an error occured while trying to download the WSDL
    }
);
```

### Client

The `Client` class is responsible for communication with the remote SOAP
WebService server.

If you want to call RPC functions, see below for the [`Proxy`](#proxy) class.

Note: It's recommended (and easier) to wrap the `Client` in a [`Proxy`](#proxy) instance.
All public methods of the `Client` are considered *advanced usage*.

#### soapCall()

The `soapCall($method, $arguments)` method can be used to queue the given
function to be sent via SOAP and wait for a response from the remote web service.

Note: This is considered *advanced usage*, you may want to look into using the [`Proxy`](#proxy) instead.

#### getFunctions()

The `getFunctions()` method returns an array of functions defined in the WSDL.
It returns the equivalent of PHP's [`SoapClient::__getFunctions()`](http://php.net/manual/en/soapclient.getfunctions.php).

#### getTypes()

The `getTypes()` method returns an array of types defined in the WSDL.
It returns the equivalent of PHP's [`SoapClient::__getTypes()`](http://php.net/manual/en/soapclient.gettypes.php).

#### withTarget($newTarget)

This method allows you to change the destination of your SOAP calls. It does not change the Client object, but returns a new
Client with the overriden target.

#### getWsdlTarget()

This method allows you to retrieve the target URL specified in the WSDL file.

### Proxy

The `Proxy` class wraps an existing [`Client`](#client) instance in order to ease calling
SOAP functions.

```php
$proxy = new Proxy($client);
```

#### Functions

Each and every method call to the `Proxy` class will be sent via SOAP.

```php
$proxy->myMethod($myArg1, $myArg2)->then(function ($response) {
    // result received
});
```

Please refer to your WSDL or its accompanying documentation for details
on which functions and arguments are supported.

#### Processing

Issuing SOAP functions is async (non-blocking), so you can actually send multiple RPC requests in parallel.
The web service will respond to each request with a return value. The order is not guaranteed.
Sending requests uses a [Promise](https://github.com/reactphp/promise)-based interface that makes it easy to react to when a request is *fulfilled*
(i.e. either successfully resolved or rejected with an error):

```php
$proxy->demo()->then(
    function ($response) {
        // response received for demo function
    },
    function (Exception $e) {
        // an error occured while executing the request
    }
});
```

## Install

The recommended way to install this library is [through composer](http://getcomposer.org).
[New to composer?](http://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/soap-react": "~0.1.0"
    }
}
```

## License

MIT
