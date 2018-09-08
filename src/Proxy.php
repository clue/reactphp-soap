<?php

namespace Clue\React\Soap;

use React\Promise\PromiseInterface;

/**
 * The `Proxy` class wraps an existing [`Client`](#client) instance in order to ease calling
 * SOAP functions.
 *
 * ```php
 * $proxy = new Proxy($client);
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
    public function __call($name, $args)
    {
        return $this->client->soapCall($name, $args);
    }
}
