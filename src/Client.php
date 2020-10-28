<?php

namespace Clue\React\Soap;

use Clue\React\Soap\Protocol\ClientDecoder;
use Clue\React\Soap\Protocol\ClientEncoder;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * The `Client` class is responsible for communication with the remote SOAP
 * WebService server.
 *
 * It requires a [`Browser`](https://github.com/reactphp/http#browser) object
 * bound to the main [`EventLoop`](https://github.com/reactphp/event-loop#usage)
 * in order to handle async requests, the WSDL file contents and an optional
 * array of SOAP options:
 *
 * ```php
 * $loop = React\EventLoop\Factory::create();
 * $browser = new React\Http\Browser($loop);
 *
 * $wsdl = '<?xml …';
 * $options = array();
 *
 * $client = new Clue\React\Soap\Client($browser, $wsdl, $options);
 * ```
 *
 * If you need custom connector settings (DNS resolution, TLS parameters, timeouts,
 * proxy servers etc.), you can explicitly pass a custom instance of the
 * [`ConnectorInterface`](https://github.com/reactphp/socket#connectorinterface)
 * to the [`Browser`](https://github.com/clue/reactphp/http#browser) instance:
 *
 * ```php
 * $connector = new React\Socket\Connector($loop, array(
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
 * $browser = new React\Http\Browser($loop, $connector);
 * $client = new Clue\React\Soap\Client($browser, $wsdl);
 * ```
 *
 * The `Client` works similar to PHP's `SoapClient` (which it uses under the
 * hood), but leaves you the responsibility to load the WSDL file. This allows
 * you to use local WSDL files, WSDL files from a cache or the most common form,
 * downloading the WSDL file contents from an URL through the `Browser`:
 *
 * ```php
 * $browser = new React\Http\Browser($loop);
 *
 * $browser->get($url)->then(
 *     function (Psr\Http\Message\ResponseInterface $response) use ($browser) {
 *         // WSDL file is ready, create client
 *         $client = new Clue\React\Soap\Client($browser, (string)$response->getBody());
 *
 *         // do something…
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
 *     $client = new Clue\React\Soap\Client($browser, $wsdl);
 * } catch (SoapFault $e) {
 *     echo 'Error: ' . $e->getMessage() . PHP_EOL;
 * }
 * ```
 *
 * > Note that if you have an old version of `ext-xdebug` < 2.7 loaded, this may
 *   halt with a fatal error instead of throwing a `SoapFault`. It is not
 *   recommended to use this extension in production, so this should only ever
 *   affect test environments.
 *
 * The `Client` constructor accepts an array of options. All given options will
 * be passed through to the underlying `SoapClient`. However, not all options
 * make sense in this async implementation and as such may not have the desired
 * effect. See also [`SoapClient`](https://www.php.net/manual/en/soapclient.soapclient.php)
 * documentation for more details.
 *
 * If working in WSDL mode, the `$options` parameter is optional. If working in
 * non-WSDL mode, the WSDL parameter must be set to `null` and the options
 * parameter must contain the `location` and `uri` options, where `location` is
 * the URL of the SOAP server to send the request to, and `uri` is the target
 * namespace of the SOAP service:
 *
 * ```php
 * $client = new Clue\React\Soap\Client($browser, null, array(
 *     'location' => 'http://example.com',
 *     'uri' => 'http://ping.example.com',
 * ));
 * ```
 *
 * Similarly, if working in WSDL mode, the `location` option can be used to
 * explicitly overwrite the URL of the SOAP server to send the request to:
 *
 * ```php
 * $client = new Clue\React\Soap\Client($browser, $wsdl, array(
 *     'location' => 'http://example.com'
 * ));
 * ```
 *
 * You can use the `soap_version` option to change from the default SOAP 1.1 to
 * use SOAP 1.2 instead:
 *
 * ```php
 * $client = new Clue\React\Soap\Client($browser, $wsdl, array(
 *     'soap_version' => SOAP_1_2
 * ));
 * ```
 *
 * You can use the `classmap` option to map certain WSDL types to PHP classes
 * like this:
 *
 * ```php
 * $client = new Clue\React\Soap\Client($browser, $wsdl, array(
 *     'classmap' => array(
 *         'getBankResponseType' => BankResponse::class
 *     )
 * ));
 * ```
 *
 * The `proxy_host` option (and family) is not supported by this library. As an
 * alternative, you can configure the given `$browser` instance to use an
 * [HTTP proxy server](https://github.com/clue/reactphp/http#http-proxy).
 * If you find any other option is missing or not supported here, PRs are much
 * appreciated!
 *
 * All public methods of the `Client` are considered *advanced usage*.
 * If you want to call RPC functions, see below for the [`Proxy`](#proxy) class.
 */
class Client
{
    private $browser;
    private $encoder;
    private $decoder;

    /**
     * Instantiate a new SOAP client for the given WSDL contents.
     *
     * @param Browser     $browser
     * @param string|null $wsdlContents
     * @param array       $options
     */
    public function __construct(Browser $browser, ?string $wsdlContents, array $options = array())
    {
        $wsdl = $wsdlContents !== null ? 'data://text/plain;base64,' . base64_encode($wsdlContents) : null;

        // Accept HTTP responses with error status codes as valid responses.
        // This is done in order to process these error responses through the normal SOAP decoder.
        // Additionally, we explicitly limit number of redirects to zero because following redirects makes little sense
        // because it transforms the POST request to a GET one and hence loses the SOAP request body.
        $browser = $browser->withRejectErrorResponse(false);
        $browser = $browser->withFollowRedirects(0);

        $this->browser = $browser;
        $this->encoder = new ClientEncoder($wsdl, $options);
        $this->decoder = new ClientDecoder($wsdl, $options);
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
     * $proxy = new Clue\React\Soap\Proxy($client);
     * $promise = $proxy->ping('hello', 42);
     * ```
     *
     * @param string  $name
     * @param mixed[] $args
     * @return PromiseInterface Returns a Promise<mixed, Exception>
     */
    public function soapCall(string $name, array $args): PromiseInterface
    {
        try {
            $request = $this->encoder->encode($name, $args);
        } catch (\Exception $e) {
            $deferred = new Deferred();
            $deferred->reject($e);
            return $deferred->promise();
        }

        $decoder = $this->decoder;

        return $this->browser->request(
            $request->getMethod(),
            (string) $request->getUri(),
            $request->getHeaders(),
            (string) $request->getBody()
        )->then(
            function (ResponseInterface $response) use ($decoder, $name) {
                // HTTP response received => decode results for this function call
                return $decoder->decode($name, (string)$response->getBody());
            }
        );
    }

    /**
     * Returns an array of functions defined in the WSDL.
     *
     * It returns the equivalent of PHP's
     * [`SoapClient::__getFunctions()`](https://www.php.net/manual/en/soapclient.getfunctions.php).
     * In non-WSDL mode, this method returns `null`.
     *
     * @return string[]|null
     */
    public function getFunctions(): ?array
    {
        return $this->encoder->__getFunctions();
    }

    /**
     * Returns an array of types defined in the WSDL.
     *
     * It returns the equivalent of PHP's
     * [`SoapClient::__getTypes()`](https://www.php.net/manual/en/soapclient.gettypes.php).
     * In non-WSDL mode, this method returns `null`.
     *
     * @return string[]|null
     */
    public function getTypes(): ?array
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
     * When the `location` option has been set in the `Client` constructor
     * (such as when in non-WSDL mode) or via the `withLocation()` method, this
     * method returns the value of the given location.
     *
     * Passing a `$function` not defined in the WSDL file will throw a `SoapFault`.
     *
     * @param string|int $function
     * @return string
     * @throws \SoapFault if given function does not exist
     * @see self::getFunctions()
     */
    public function getLocation($function): string
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
     * Returns a new `Client` with the updated location (URI) for all functions.
     *
     * Note that this is not to be confused with the WSDL file location.
     * A WSDL file can contain any number of function definitions.
     * It's very common that all of these functions use the same location definition.
     * However, technically each function can potentially use a different location.
     *
     * ```php
     * $client = $client->withLocation('http://example.com/soap');
     *
     * assert('http://example.com/soap' === $client->getLocation('echo'));
     * ```
     *
     * As an alternative to this method, you can also set the `location` option
     * in the `Client` constructor (such as when in non-WSDL mode).
     *
     * @param string $location
     * @return self
     * @see self::getLocation()
     */
    public function withLocation(string $location): self
    {
        $client = clone $this;
        $client->encoder = clone $this->encoder;
        $client->encoder->__setLocation($location);

        return $client;
    }
}
