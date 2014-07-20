<?php

namespace Clue\React\Soap\Protocol;

use \SoapClient;

class ClientDecoder extends SoapClient
{
    private $response = null;

    public function __construct()
    {
        // to not pass actual WSDL to parent constructor
        // use faked non-wsdl-mode to let every method call pass through (pseudoCall)
        parent::__construct(null, array('location' => '1', 'uri' => '2'));
    }

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

    public function __doRequest($request, $location, $action, $version)
    {
        // the actual result doesn't actually matter, just return the given result
        // this will be processed internally and will return the parsed result
        return $this->response;
    }
}
