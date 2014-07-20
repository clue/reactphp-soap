<?php

namespace Clue\React\Soap\Protocol;

use Clue\React\Buzz\Browser;
use \SoapClient;

class ClientEncoder extends SoapClient
{
    private $browser;
    public $pending = null;

    public function __construct($wsdl, Browser $browser)
    {
        parent::__construct($wsdl);
        $this->browser = $browser;
    }

    public function __doRequest($request, $location, $action, $version)
    {
        $this->pending = $this->browser->post(
            $location,
            array(
                'SOAPAction' => $action,
                'Content-Type' => 'text/xml; charset=utf-8',
                'Content-Length' => strlen($request)
            ),
            $request
        );

        // do not actually block here, just pretend we're done...
        return '';
    }
}
