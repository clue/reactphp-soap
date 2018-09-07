<?php

namespace Clue\React\Soap;

final class Proxy
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function __call($name, $args)
    {
        return $this->client->soapCall($name, $args);
    }
}
