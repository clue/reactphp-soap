<?php

namespace Clue\React\Soap\Protocol;

use \SoapClient;

/**
 * @internal
 */
final class ClientDecoder extends SoapClient
{
    private $response = null;

    public function __construct()
    {
        // to not pass actual WSDL to parent constructor
        // use faked non-wsdl-mode to let every method call pass through (pseudoCall)
        parent::__construct(null, array('location' => '1', 'uri' => '2'));
    }

    /**
     * Decodes the SOAP response / return value from the given SOAP envelope (HTTP response body)
     *
     * @param string $response
     * @return mixed
     * @throws \SoapFault if response indicates a fault (error condition) or is invalid
     */
    public function decode($response)
    {
        // temporarily save response internally for further processing
        $this->response = $response;

        // pretend we just invoked a SOAP function.
        // internally, use the injected response to parse its results
        $ret = $this->pseudoCall();
        $this->response = null;

        return $ret;
    }

    /**
     * Overwrites the internal request logic to parse the response
     *
     * By overwriting this method, we can skip the actual request sending logic
     * and still use the internal parsing logic by injecting the response as
     * the return code in this method. This will implicitly be invoked by the
     * call to `pseudoCall()` in the above `decode()` method.
     *
     * @see SoapClient::__doRequest()
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        // the actual result doesn't actually matter, just return the given result
        // this will be processed internally and will return the parsed result
        return $this->response;
    }
}
