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
 * It requires a [`Browser`](https://github.com/clue/reactphp-buzz#browser) object
 * bound to the main [`EventLoop`](https://github.com/reactphp/event-loop#usage)
 * in order to handle async requests and the WSDL file contents:
 *
 * ```php
 * $loop = React\EventLoop\Factory::create();
 * $browser = new Clue\React\Buzz\Browser($loop);
 *
 * $client = new Client($browser, $wsdl);
 * ```
 *
 * If you need custom DNS, TLS or proxy settings, you can explicitly pass a
 * custom [`Browser`](https://github.com/clue/reactphp-buzz#browser) instance:
 *
 * ```php
 * $connector = new \React\Socket\Connector($loop, array(
 *     'dns' => '127.0.0.1',
 *     'tcp' => array(
 *         'bindto' => '192.168.10.1:0'
 *     ),
 *     'tls' => array(
 *         'verify_peer' => false,
 *         'verify_peer_name' => false
 *     )
 * ));
 *
 * $browser = new Browser($loop, $connector);
 * $client = new Client($browser, $wsdl);
 * ```
 *
 * The `Client` works similar to PHP's `SoapClient` (which it uses under the
 * hood), but leaves you the responsibility to load the WSDL file. This allows
 * you to use local WSDL files, WSDL files from a cache or the most common form,
 * downloading the WSDL file contents from an URL through the `Browser`:
 *
 * ```php
 * $browser = new Browser($loop);
 *
 * $browser->get($url)->then(
 *     function (ResponseInterface $response) use ($browser) {
 *         // WSDL file is ready, create client
 *         $client = new Client($browser, (string)$response->getBody());
 *         …
 *     },
 *     function (Exception $e) {
 *         // an error occured while trying to download the WSDL
 *     }
 * );
 * ```
 *
 * The `Client` constructor loads the given WSDL file contents into memory and
 * parses its definition. If the given WSDL file is invalid and can not be
 * parsed, this will throw a `SoapFault`:
 *
 * ```php
 * try {
 *     $client = new Client($browser, $wsdl);
 * } catch (SoapFault $e) {
 *     echo 'Error: ' . $e->getMessage() . PHP_EOL;
 * }
 * ```
 *
 * > Note that if you have `ext-debug` loaded, this may halt with a fatal
 *   error instead of throwing a `SoapFault`. It is not recommended to use this
 *   extension in production, so this should only ever affect test environments.
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
     * Instantiate a new SOAP client for the given WSDL contents.
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
