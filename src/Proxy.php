<?php

namespace Clue\React\Soap;

class Proxy
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function __call($name, $args)
    {
        return $this->client->soapCall($name, $args);
    }
}
