<?php

namespace Clue\React\Soap;

use Clue\React\Buzz\Browser;
use Exception;
use Clue\React\Soap\Protocol\ClientEncoder;
use Clue\React\Soap\Protocol\ClientDecoder;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;

class Client
{
    private $wsdl;
    private $browser;
    private $encoder;
    private $decoder;

    public function __construct($wsdl, Browser $browser, ClientEncoder $encoder = null, ClientDecoder $decoder = null)
    {
        if ($encoder === null) {
            $encoder = new ClientEncoder($wsdl);
        }

        if ($decoder === null) {
            $decoder = new ClientDecoder($wsdl);
        }

        $this->wsdl = $wsdl;
        $this->browser = $browser;
        $this->encoder = $encoder;
        $this->decoder = $decoder;
    }

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
            array($this, 'handleResponse'),
            array($this, 'handleError')
        );
    }

    public function handleResponse(ResponseInterface $response)
    {
        return $this->decoder->decode((string) $response->getBody());
    }

    public function handleError(Exception $error)
    {
        throw $error;
    }

    public function getFunctions()
    {
        return $this->encoder->__getFunctions();
    }

    public function getTypes()
    {
        return $this->encoder->__getTypes();
    }

    /**
     * get location (URI) for given function name or number
     *
     * Note that this is not to be confused with the WSDL file location.
     * A WSDL file can contain any number of function definitions.
     * It's very common that all of these functions use the same location definition.
     * However, technically each function can potentially use a different location.
     *
     * The `$function` parameter should be a string with the the SOAP function name.
     * See also `getFunctions()` for a list of all available functions.
     *
     * For easier access, this function also accepts a numeric function index.
     * It then uses `getFunctions()` internally to get the function
     * name for the given index.
     * This is particularly useful for the very common case where all functions use the
     * same location and accessing the first location is sufficient.
     *
     * @param string|int $function
     * @return string
     * @throws SoapFault if given function does not exist
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

    public function withTarget($target)
    {
        $copy = clone $this;
        $copy->encoder = $this->encoder->withTarget($target);
        return $copy;
    }
}
