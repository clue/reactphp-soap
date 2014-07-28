# clue/soap-react [![Build Status](https://travis-ci.org/clue/php-soap-react.svg?branch=master)](https://travis-ci.org/clue/php-soap-react)

Simple, async SOAP webservice client library

> Note: This project is in beta stage! Feel free to report any issues you encounter.

## Quickstart example

Once [installed](#install), you can use the following code to query an example
web service via SOAP:

```php
$factory = new Factory($loop);
$wsdl = 'http://example.com/demo.wsdl';

$factory->createClient($wsdl)->then(function (Client $client) {
    $api = new Proxy($client);

    $api->getBank(array('blz' => '12070000'))->then(function ($result) {
        var_dump('Result', $result);
    });
});
```

See also the [examples](examples).

## Usage

### Factory

The `Factory` class is responsible for fetching the WSDL file once and constructing
the `Client` instance.

### Client

The `Client` class is responsible for communication with the remove SOAP
WebService server.

> Note: It's recommended (and easier) to wrap the `Client` in a `Proxy` instance
> (see below). The rest of this chapter is considered advanced usage.

The `soapCall($method, $arguments)` method can be used to queue the given
function to be sent via SOAP and wait for a response from the remote web service.

The `getFunctions()` method returns an array of functions defined in the WSDL.

The `getTypes()` method returns an array of types defined in the WSDL.

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
