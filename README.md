# clue/reactphp-soap [![Build Status](https://travis-ci.org/clue/reactphp-soap.svg?branch=master)](https://travis-ci.org/clue/reactphp-soap)

Simple, async [SOAP](https://en.wikipedia.org/wiki/SOAP) web service client library,
built on top of [ReactPHP](https://reactphp.org/).

Most notably, SOAP is often used for invoking
[Remote procedure calls](https://en.wikipedia.org/wiki/Remote_procedure_call) (RPCs)
in distributed systems.
Internally, SOAP messages are encoded as XML and usually sent via HTTP POST requests.
For the most part, SOAP (originally *Simple Object Access protocol*) is a protocol of the past,
and in fact anything but *simple*.
It is still in use by many (often *legacy*) systems.
This project provides a *simple* API for invoking *async* RPCs to remote web services.

* **Async execution of functions** -
  Send any number of functions (RPCs) to the remote web service in parallel and
  process their responses as soon as results come in.
  The Promise-based design provides a *sane* interface to working with out of order responses.
* **Async processing of the WSDL** -
  The WSDL (web service description language) file will be downloaded and processed
  in the background.
* **Event-driven core** -
  Internally, everything uses event handlers to react to incoming events, such as an incoming RPC result.
* **Lightweight, SOLID design** -
  Provides a thin abstraction that is [*just good enough*](https://en.wikipedia.org/wiki/Principle_of_good_enough)
  and does not get in your way.
  Built on top of tested components instead of re-inventing the wheel.
* **Good test coverage** -
  Comes with an automated tests suite and is regularly tested against actual web services in the wild.

**Table of contents**

* [Support us](#support-us)
* [Quickstart example](#quickstart-example)
* [Usage](#usage)
  * [Client](#client)
    * [soapCall()](#soapcall)
    * [getFunctions()](#getfunctions)
    * [getTypes()](#gettypes)
    * [getLocation()](#getlocation)
    * [withLocation()](#withlocation)
  * [Proxy](#proxy)
    * [Functions](#functions)
    * [Promises](#promises)
    * [Cancellation](#cancellation)
    * [Timeouts](#timeouts)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Support us

We invest a lot of time developing, maintaining and updating our awesome
open-source projects. You can help us sustain this high-quality of our work by
[becoming a sponsor on GitHub](https://github.com/sponsors/clue). Sponsors get
numerous benefits in return, see our [sponsoring page](https://github.com/sponsors/clue)
for details.

Let's take these projects to the next level together! 🚀

## Quickstart example

Once [installed](#install), you can use the following code to query an example
web service via SOAP:

```php
$loop = React\EventLoop\Factory::create();
$browser = new React\Http\Browser($loop);
$wsdl = 'http://example.com/demo.wsdl';

$browser->get($wsdl)->then(function (Psr\Http\Message\ResponseInterface $response) use ($browser) {
    $client = new Clue\React\Soap\Client($browser, (string)$response->getBody());
    $api = new Clue\React\Soap\Proxy($client);

    $api->getBank(array('blz' => '12070000'))->then(function ($result) {
        var_dump('Result', $result);
    });
});

$loop->run();
```

See also the [examples](examples).

## Usage

### Client

The `Client` class is responsible for communication with the remote SOAP
WebService server.

It requires a [`Browser`](https://github.com/reactphp/http#browser) object
bound to the main [`EventLoop`](https://github.com/reactphp/event-loop#usage)
in order to handle async requests, the WSDL file contents and an optional
array of SOAP options:

```php
$loop = React\EventLoop\Factory::create();
$browser = new React\Http\Browser($loop);

$wsdl = '<?xml …';
$options = array();

$client = new Clue\React\Soap\Client($browser, $wsdl, $options);
```

If you need custom connector settings (DNS resolution, TLS parameters, timeouts,
proxy servers etc.), you can explicitly pass a custom instance of the
[`ConnectorInterface`](https://github.com/reactphp/socket#connectorinterface)
to the [`Browser`](https://github.com/reactphp/http#browser) instance:

```php
$connector = new React\Socket\Connector($loop, array(
    'dns' => '127.0.0.1',
    'tcp' => array(
        'bindto' => '192.168.10.1:0'
    ),
    'tls' => array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
));

$browser = new React\Http\Browser($loop, $connector);
$client = new Clue\React\Soap\Client($browser, $wsdl);
```

The `Client` works similar to PHP's `SoapClient` (which it uses under the
hood), but leaves you the responsibility to load the WSDL file. This allows
you to use local WSDL files, WSDL files from a cache or the most common form,
downloading the WSDL file contents from an URL through the `Browser`:

```php
$browser = new React\Http\Browser($loop);

$browser->get($url)->then(
    function (Psr\Http\Message\ResponseInterface $response) use ($browser) {
        // WSDL file is ready, create client
        $client = new Clue\React\Soap\Client($browser, (string)$response->getBody());

        // do something…
    },
    function (Exception $e) {
        // an error occured while trying to download the WSDL
    }
);
```

The `Client` constructor loads the given WSDL file contents into memory and
parses its definition. If the given WSDL file is invalid and can not be
parsed, this will throw a `SoapFault`:

```php
try {
    $client = new Clue\React\Soap\Client($browser, $wsdl);
} catch (SoapFault $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
```

> Note that if you have an old version of `ext-xdebug` < 2.7 loaded, this may
  halt with a fatal error instead of throwing a `SoapFault`. It is not
  recommended to use this extension in production, so this should only ever
  affect test environments.

The `Client` constructor accepts an array of options. All given options will
be passed through to the underlying `SoapClient`. However, not all options
make sense in this async implementation and as such may not have the desired
effect. See also [`SoapClient`](https://www.php.net/manual/en/soapclient.soapclient.php)
documentation for more details.

If working in WSDL mode, the `$options` parameter is optional. If working in
non-WSDL mode, the WSDL parameter must be set to `null` and the options
parameter must contain the `location` and `uri` options, where `location` is
the URL of the SOAP server to send the request to, and `uri` is the target
namespace of the SOAP service:

```php
$client = new Clue\React\Soap\Client($browser, null, array(
    'location' => 'http://example.com',
    'uri' => 'http://ping.example.com',
));
```

Similarly, if working in WSDL mode, the `location` option can be used to
explicitly overwrite the URL of the SOAP server to send the request to:

```php
$client = new Clue\React\Soap\Client($browser, $wsdl, array(
    'location' => 'http://example.com'
));
```

You can use the `soap_version` option to change from the default SOAP 1.1 to
use SOAP 1.2 instead:

```php
$client = new Clue\React\Soap\Client($browser, $wsdl, array(
    'soap_version' => SOAP_1_2
));
```

You can use the `classmap` option to map certain WSDL types to PHP classes
like this:

```php
$client = new Clue\React\Soap\Client($browser, $wsdl, array(
    'classmap' => array(
        'getBankResponseType' => BankResponse::class
    )
));
```

The `proxy_host` option (and family) is not supported by this library. As an
alternative, you can configure the given `$browser` instance to use an
[HTTP proxy server](https://github.com/reactphp/http#http-proxy).
If you find any other option is missing or not supported here, PRs are much
appreciated!

All public methods of the `Client` are considered *advanced usage*.
If you want to call RPC functions, see below for the [`Proxy`](#proxy) class.

#### soapCall()

The `soapCall(string $method, mixed[] $arguments): PromiseInterface<mixed, Exception>` method can be used to
queue the given function to be sent via SOAP and wait for a response from the remote web service.

```php
// advanced usage, see Proxy for recommended alternative
$promise = $client->soapCall('ping', array('hello', 42));
```

Note: This is considered *advanced usage*, you may want to look into using the [`Proxy`](#proxy) instead.

```php
$proxy = new Clue\React\Soap\Proxy($client);
$promise = $proxy->ping('hello', 42);
```

#### getFunctions()

The `getFunctions(): string[]|null` method can be used to
return an array of functions defined in the WSDL.

It returns the equivalent of PHP's 
[`SoapClient::__getFunctions()`](https://www.php.net/manual/en/soapclient.getfunctions.php).
In non-WSDL mode, this method returns `null`.

#### getTypes()

The `getTypes(): string[]|null` method can be used to
return an array of types defined in the WSDL.

It returns the equivalent of PHP's
[`SoapClient::__getTypes()`](https://www.php.net/manual/en/soapclient.gettypes.php).
In non-WSDL mode, this method returns `null`.

#### getLocation()

The `getLocation(string|int $function): string` method can be used to
return the location (URI) of the given webservice `$function`.

Note that this is not to be confused with the WSDL file location.
A WSDL file can contain any number of function definitions.
It's very common that all of these functions use the same location definition.
However, technically each function can potentially use a different location.

The `$function` parameter should be a string with the the SOAP function name.
See also [`getFunctions()`](#getfunctions) for a list of all available functions.

```php
assert('http://example.com/soap/service' === $client->getLocation('echo'));
```

For easier access, this function also accepts a numeric function index.
It then uses [`getFunctions()`](#getfunctions) internally to get the function
name for the given index.
This is particularly useful for the very common case where all functions use the
same location and accessing the first location is sufficient.

```php
assert('http://example.com/soap/service' === $client->getLocation(0));
```

When the `location` option has been set in the `Client` constructor
(such as when in non-WSDL mode) or via the `withLocation()` method, this
method returns the value of the given location.

Passing a `$function` not defined in the WSDL file will throw a `SoapFault`.

#### withLocation()

The `withLocation(string $location): self` method can be used to
return a new `Client` with the updated location (URI) for all functions.

Note that this is not to be confused with the WSDL file location.
A WSDL file can contain any number of function definitions.
It's very common that all of these functions use the same location definition.
However, technically each function can potentially use a different location.

```php
$client = $client->withLocation('http://example.com/soap');

assert('http://example.com/soap' === $client->getLocation('echo'));
```

As an alternative to this method, you can also set the `location` option
in the `Client` constructor (such as when in non-WSDL mode).

### Proxy

The `Proxy` class wraps an existing [`Client`](#client) instance in order to ease calling
SOAP functions.

```php
$proxy = new Clue\React\Soap\Proxy($client);
```

> Note that this class is called "Proxy" because it will forward (proxy) all
  method calls to the actual SOAP service via the underlying
  [`Client::soapCall()`](#soapcall) method. This is not to be confused with
  using a proxy server. See [`Client`](#client) documentation for more
  details on how to use an HTTP proxy server.

#### Functions

Each and every method call to the `Proxy` class will be sent via SOAP.

```php
$proxy->myMethod($myArg1, $myArg2)->then(function ($response) {
    // result received
});
```

Please refer to your WSDL or its accompanying documentation for details
on which functions and arguments are supported.

#### Promises

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

#### Cancellation

The returned Promise is implemented in such a way that it can be cancelled
when it is still pending.
Cancelling a pending promise will reject its value with an Exception and
clean up any underlying resources.

```php
$promise = $proxy->demo();

$loop->addTimer(2.0, function () use ($promise) {
    $promise->cancel();
});
```

#### Timeouts

This library uses a very efficient HTTP implementation, so most SOAP requests
should usually be completed in mere milliseconds. However, when sending SOAP
requests over an unreliable network (the internet), there are a number of things
that can go wrong and may cause the request to fail after a time. As such,
timeouts are handled by the underlying HTTP library and this library respects
PHP's `default_socket_timeout` setting (default 60s) as a timeout for sending the
outgoing SOAP request and waiting for a successful response and will otherwise
cancel the pending request and reject its value with an Exception.

Note that this timeout value covers creating the underlying transport connection,
sending the SOAP request, waiting for the remote service to process the request
and receiving the full SOAP response. To use a custom timeout value, you can
pass the timeout to the [underlying `Browser`](https://github.com/reactphp/http#timeouts)
like this:

```php
$browser = new React\Http\Browser($loop);
$browser = $browser->withTimeout(10.0);

$client = new Clue\React\Soap\Client($browser, $wsdl);
$proxy = new Clue\React\Soap\Proxy($client);

$proxy->demo()->then(function ($response) {
    // response received within 10 seconds maximum
    var_dump($response);
});
```

Similarly, you can use a negative timeout value to not apply a timeout at all
or use a `null` value to restore the default handling. Note that the underlying
connection may still impose a different timeout value. See also the underlying
[timeouts documentation](https://github.com/reactphp/http#timeouts) for more details.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require clue/soap-react:^2.0
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus only requires `ext-soap` and
supports running on PHP 7.1+.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

The test suite also contains a number of functional integration tests that rely
on a stable internet connection.
If you do not want to run these, they can simply be skipped like this:

```bash
$ php vendor/bin/phpunit --exclude-group internet
```

## License

This project is released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
