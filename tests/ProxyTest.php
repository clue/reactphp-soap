<?php

use PHPUnit\Framework\TestCase;
use Clue\React\Soap\Proxy;

class ProxyTest extends TestCase
{
    public function testFunctionWillBeForwardedToClient()
    {
        $client = $this->getMockBuilder('Clue\React\Soap\Client')->disableOriginalConstructor()->getMock();
        $client->expects($this->once())->method('soapCall')->with('demo', array(1, 2));

        $proxy = new Proxy($client);
        $proxy->demo(1, 2);
    }
}
