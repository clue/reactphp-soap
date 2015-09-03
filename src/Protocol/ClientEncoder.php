<?php

namespace Clue\React\Soap\Protocol;

use \SoapClient;
use RingCentral\Psr7\Request;

class ClientEncoder extends SoapClient
{
    private $request          = null;
    private $locationOverride = null;

    public function encode($name, $args)
    {
        $this->__soapCall($name, $args);

        $request = $this->request;
        $this->request = null;

        return $request;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $finalLocation = $this->locationOverride !== null ? $this->locationOverride : $location;

        $this->request = new Request(
            'POST',
            (string)$finalLocation,
            array(
                'SOAPAction' => (string)$action,
                'Content-Type' => 'text/xml; charset=utf-8',
                'Content-Length' => strlen($request)
            ),
            (string)$request
        );

        // do not actually block here, just pretend we're done...
        return '';
    }

    public function overrideLocation($newLocation)
    {
        $copy = clone $this;
        $this->locationOverride = $newLocation;
        return $copy;
    }
}
