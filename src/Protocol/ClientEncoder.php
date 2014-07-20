<?php

namespace Clue\React\Soap\Protocol;

use Clue\React\Buzz\Browser;
use \SoapClient;
use Clue\React\Buzz\Message\Request;
use Clue\React\Buzz\Message\Headers;
use Clue\React\Buzz\Message\Body;

class ClientEncoder extends SoapClient
{
    private $request = null;

    public function encode($name, $args)
    {
        $this->__soapCall($name, $args);

        $request = $this->request;
        $this->request = null;

        return $request;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $this->request = new Request(
            'POST',
            $location,
            new Headers(array(
                'SOAPAction' => $action,
                'Content-Type' => 'text/xml; charset=utf-8',
                'Content-Length' => strlen($request)
            )),
            new Body($request)
        );

        // do not actually block here, just pretend we're done...
        return '';
    }
}
