<?php

namespace Clue\React\Soap;

use Clue\React\Buzz\Browser;
use Clue\React\Soap\Protocol\ClientDecoder;
use Clue\React\Soap\Protocol\ClientEncoder;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * The `Client` class is responsible for communication with the remote SOAP
 * WebService server.
 *
 * If you want to call RPC functions, see below for the [`Proxy`](#proxy) class.
 *
 * Note: It's recommended (and easier) to wrap the `Client` in a [`Proxy`](#proxy) instance.
 * All public methods of the `Client` are considered *advanced usage*.
 */
final class Client
{
    private $browser;
    private $encoder;
    private $decoder;

    /**
     * Instantiate new SOAP client
     *
     * @param Browser $browser
     * @param string  $wsdlContents
     */
    public function __construct(Browser $browser, $wsdlContents)
    {
        $this->browser = $browser;
        $this->encoder = new ClientEncoder(
            'data://text/plain;base64,' . base64_encode($wsdlContents)
        );
        $this->decoder = new ClientDecoder();
    }

    /**
     * Queue the given function to be sent via SOAP and wait for a response from the remote web service.
     *
     * ```php
     * // advanced usage, see Proxy for recommended alternative
     * $promise = $client->soapCall('ping', array('hello', 42));
     * ```
     *
     * Note: This is considered *advanced usage*, you may want to look into using the [`Proxy`](#proxy) instead.
     *
     * ```php
     * $proxy = new Proxy($client);
     * $promise = $proxy->ping('hello', 42);
     * ```
     *
     * @param string  $name
     * @param mixed[] $args
     * @return PromiseInterface Returns a Promise<mixed, Exception>
     */
    public function soapCall($name, $args)
    {
        try {
            $request = $this->encoder->encode($name, $args);
        } catch (\Exception $e) {
            $deferred = new Deferred();
            $deferred->reject($e);
            return $deferred->promise();
        }

        return $this->browser->send($request)->then(
            array($this, 'handleResponse')
        );
    }


    /**
     * Returns an array of functions defined in the WSDL.
     *
     * It returns the equivalent of PHP's
     * [`SoapClient::__getFunctions()`](http://php.net/manual/en/soapclient.getfunctions.php).
     *
     * @return string[]
     */
    public function getFunctions()
    {
        return $this->encoder->__getFunctions();
    }

    /**
     * Returns an array of types defined in the WSDL.
     *
     * It returns the equivalent of PHP's
     * [`SoapClient::__getTypes()`](http://php.net/manual/en/soapclient.gettypes.php).
     *
     * @return string[]
     */
    public function getTypes()
    {
        return $this->encoder->__getTypes();
    }

    /**
     * Returns the location (URI) of the given webservice `$function`.
     *
     * Note that this is not to be confused with the WSDL file location.
     * A WSDL file can contain any number of function definitions.
     * It's very common that all of these functions use the same location definition.
     * However, technically each function can potentially use a different location.
     *
     * The `$function` parameter should be a string with the the SOAP function name.
     * See also [`getFunctions()`](#getfunctions) for a list of all available functions.
     *
     * ```php
     * assert('http://example.com/soap/service' === $client->getLocation('echo'));
     * ```
     *
     * For easier access, this function also accepts a numeric function index.
     * It then uses [`getFunctions()`](#getfunctions) internally to get the function
     * name for the given index.
     * This is particularly useful for the very common case where all functions use the
     * same location and accessing the first location is sufficient.
     *
     * ```php
     * assert('http://example.com/soap/service' === $client->getLocation(0));
     * ```
     *
     * Passing a `$function` not defined in the WSDL file will throw a `SoapFault`.
     *
     * @param string|int $function
     * @return string
     * @throws \SoapFault if given function does not exist
     * @see self::getFunctions()
     */
    public function getLocation($function)
    {
        if (is_int($function)) {
            $functions = $this->getFunctions();
            if (isset($functions[$function]) && preg_match('/^\w+ (\w+)\(/', $functions[$function], $match)) {
                $function = $match[1];
            }
        }

        // encode request for given $function
        return (string)$this->encoder->encode($function, array())->getUri();
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     * @internal
     */
    public function handleResponse(ResponseInterface $response)
    {
        return $this->decoder->decode((string)$response->getBody());
    }
}
