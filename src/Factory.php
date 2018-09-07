<?php

namespace Clue\React\Soap;

use React\EventLoop\LoopInterface;
use Clue\React\Buzz\Browser;
use Psr\Http\Message\ResponseInterface;

final class Factory
{
    private $loop;
    private $browser;

    public function __construct(LoopInterface $loop, Browser $browser = null)
    {
        if ($browser === null) {
            $browser = new Browser($loop);
        }
        $this->loop = $loop;
        $this->browser = $browser;
    }

    public function createClient($wsdl)
    {
        $that = $this;

        return $this->browser->get($wsdl)->then(function (ResponseInterface $response) use ($that) {
            return $that->createClientFromWsdl((string)$response->getBody());
        });
    }

    public function createClientFromWsdl($wsdlContents)
    {
        $browser = $this->browser;
        $url     = 'data://text/plain;base64,' . base64_encode($wsdlContents);

        return new Client($url, $browser);
    }
}
