<?php

namespace Clue\React\Soap;

use React\EventLoop\LoopInterface;
use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\Response;

class Factory
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
        $browser = $this->browser;

        return $this->browser->get($wsdl)->then(function (Response $response) {
            $url = 'data://text/plain;base64,' . base64_encode((string)$response->getBody());

            return new Client($url, $this->browser);
        });
    }
}
