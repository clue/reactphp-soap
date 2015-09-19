<?php

namespace Clue\React\Soap\Protocol;

use \SoapClient;
use RingCentral\Psr7\Request;

class ClientEncoder extends SoapClient
{
    private $request        = null;
    private $targetOverride = null;

    public function encode($name, $args)
    {
        $this->__soapCall($name, $args);

        $request = $this->request;
        $this->request = null;

        return $request;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {

        $finalLocation = $this->targetOverride !== null ? $this->targetOverride : $location;

        $this->request = new Request(
            'POST',
            (string) $finalLocation,
            new Headers(array(
                'SOAPAction' => (string) $action,
                'Content-Type' => 'text/xml; charset=utf-8',
                'Content-Length' => strlen($request)
            )),
            new Body((string) $request)
        );

        // do not actually block here, just pretend we're done...
        return '';
    }

    public function withTarget($newTarget)
    {
        $copy = clone $this;
        $copy->targetOverride = $newTarget;
        return $copy;
    }
}
