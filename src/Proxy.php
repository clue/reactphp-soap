<?php

namespace Clue\React\Soap;

use React\Promise\PromiseInterface;

/**
 * The `Proxy` class wraps an existing [`Client`](#client) instance in order to ease calling
 * SOAP functions.
 *
 * ```php
 * $proxy = new Clue\React\Soap\Proxy($client);
 * ```
 *
 * Each and every method call to the `Proxy` class will be sent via SOAP.
 *
 * ```php
 * $proxy->myMethod($myArg1, $myArg2)->then(function ($response) {
 *     // result received
 * });
 * ```
 *
 * Please refer to your WSDL or its accompanying documentation for details
 * on which functions and arguments are supported.
 *
 * > Note that this class is called "Proxy" because it will forward (proxy) all
 *   method calls to the actual SOAP service via the underlying
 *   [`Client::soapCall()`](#soapcall) method. This is not to be confused with
 *   using a proxy server. See [`Client`](#client) documentation for more
 *   details on how to use an HTTP proxy server.
 */
final class Proxy
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string  $name
     * @param mixed[] $args
     * @return PromiseInterface
     */
    public function __call(string $name, array $args): PromiseInterface
    {
        return $this->client->soapCall($name, $args);
    }
}
