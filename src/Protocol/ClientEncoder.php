<?php

namespace Clue\React\Soap\Protocol;

use Clue\React\Buzz\Browser;
use \SoapClient;
use Clue\React\Buzz\Message\Request;
use Clue\React\Buzz\Message\Headers;
use Clue\React\Buzz\Message\Body;

class ClientEncoder extends SoapClient
{
    private $request        = null;
    private $targetOverride = null;
    private $target         = null;
    private $findTarget     = false;

    public function encode($name, $args)
    {
        $this->__soapCall($name, $args);

        $request = $this->request;
        $this->request = null;

        return $request;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        if ($this->findTarget) {
            $this->target     = (string) $location;
            $this->findTarget = false;
        } else {
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
        }

        // do not actually block here, just pretend we're done...
        return '';
    }

    public function withTarget($newTarget)
    {
        $copy = clone $this;
        $this->targetOverride = $newTarget;
        return $copy;
    }

    public function getWsdlTarget()
    {
        /*
        * We can't just use a function with an empty name.
        * SoapClient complains if the request does not exist.
        */
        $functionDescriptions = $this->__getFunctions();
        $functionDescription = $functionDescriptions[0]; /* PHP 5.3 support. */
        $spaceIndex = strpos($functionDescription, ' ');
        $openingParenIndex = strpos($functionDescription, '(');
        $function = substr(
            $functionDescription,
            $spaceIndex + 1,
            $openingParenIndex - $spaceIndex - 1
        );

        $this->findTarget = true;
        $this->__soapCall($function, array());
        return $this->target;
    }

}
