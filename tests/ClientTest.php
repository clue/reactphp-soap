<?php

namespace Clue\Tests\React\Soap;

use Clue\React\Soap\Client;
use PHPUnit\Framework\TestCase;
use React\Promise\Promise;
use Psr\Http\Message\RequestInterface;

class ClientTest extends TestCase
{
    public function testConstructorThrowsWhenUrlIsInvalid()
    {
        if (extension_loaded('xdebug')) {
            $this->markTestSkipped('Invalid WSDL causes a fatal error when ext-xdebug is loaded');
        }

        $browser = $this->getMockBuilder('Clue\React\Buzz\Browser')->disableOriginalConstructor()->getMock();
        $wsdl = 'invalid';

        $client = new Client($browser, $wsdl);
    }

    public function testNonWsdlClientReturnsSameLocationOptionForAnyFunction()
    {
        $browser = $this->getMockBuilder('Clue\React\Buzz\Browser')->disableOriginalConstructor()->getMock();

        $client = new Client($browser, null, array('location' => 'http://example.com', 'uri' => 'http://example.com/uri'));

        $this->assertEquals('http://example.com', $client->getLocation('anything'));
    }

    public function testNonWsdlClientReturnsNoTypesAndFunctions()
    {
        $browser = $this->getMockBuilder('Clue\React\Buzz\Browser')->disableOriginalConstructor()->getMock();

        $client = new Client($browser, null, array('location' => 'http://example.com', 'uri' => 'http://example.com/uri'));

        $this->assertNull($client->getTypes());
        $this->assertNull($client->getFunctions());
    }

    public function testNonWsdlClientSendsPostRequestToGivenLocationForAnySoapCall()
    {
        $verify = function (RequestInterface $request) {
            return ($request->getMethod() === 'POST' && (string)$request->getUri() === 'http://example.com');
        };
        $promise = new Promise(function () { });
        $browser = $this->getMockBuilder('Clue\React\Buzz\Browser')->disableOriginalConstructor()->getMock();
        $browser->expects($this->once())->method('withOptions')->willReturnSelf();
        $browser->expects($this->once())->method('send')->with($this->callback($verify))->willReturn($promise);

        $client = new Client($browser, null, array('location' => 'http://example.com', 'uri' => 'http://example.com/uri'));

        $client->soapCall('ping', array());
    }
}
