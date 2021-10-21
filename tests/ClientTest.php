<?php

namespace Clue\Tests\React\Soap;

use Clue\React\Soap\Client;
use PHPUnit\Framework\TestCase;
use React\Promise\Promise;

class ClientTest extends TestCase
{
    public function testConstructorThrowsWhenUrlIsInvalid()
    {
        if (extension_loaded('xdebug') && phpversion('xdebug') < 2.7) {
            $this->markTestSkipped('Invalid WSDL causes a fatal error when ext-xdebug < 2.7 is loaded');
        }

        $browser = $this->getMockBuilder('React\Http\Browser')->disableOriginalConstructor()->getMock();
        $browser->expects($this->once())->method('withRejectErrorResponse')->willReturnSelf();
        $browser->expects($this->once())->method('withFollowRedirects')->willReturnSelf();

        $wsdl = 'invalid';

        $this->expectException(\SoapFault::class);
        new Client($browser, $wsdl);
    }

    public function testNonWsdlClientReturnsSameLocationOptionForAnyFunction()
    {
        $browser = $this->getMockBuilder('React\Http\Browser')->disableOriginalConstructor()->getMock();
        $browser->expects($this->once())->method('withRejectErrorResponse')->willReturnSelf();
        $browser->expects($this->once())->method('withFollowRedirects')->willReturnSelf();

        $client = new Client($browser, null, array('location' => 'http://example.com', 'uri' => 'http://example.com/uri'));

        $this->assertEquals('http://example.com', $client->getLocation('anything'));
    }

    public function testNonWsdlClientReturnsNoTypesAndFunctions()
    {
        $browser = $this->getMockBuilder('React\Http\Browser')->disableOriginalConstructor()->getMock();
        $browser->expects($this->once())->method('withRejectErrorResponse')->willReturnSelf();
        $browser->expects($this->once())->method('withFollowRedirects')->willReturnSelf();

        $client = new Client($browser, null, array('location' => 'http://example.com', 'uri' => 'http://example.com/uri'));

        $this->assertNull($client->getTypes());
        $this->assertNull($client->getFunctions());
    }

    public function testNonWsdlClientSendsPostRequestToGivenLocationForAnySoapCall()
    {
        $promise = new Promise(function () { });
        $browser = $this->getMockBuilder('React\Http\Browser')->disableOriginalConstructor()->getMock();
        $browser->expects($this->once())->method('withRejectErrorResponse')->willReturnSelf();
        $browser->expects($this->once())->method('withFollowRedirects')->willReturnSelf();
        $browser->expects($this->once())->method('request')->with('POST', 'http://example.com')->willReturn($promise);

        $client = new Client($browser, null, array('location' => 'http://example.com', 'uri' => 'http://example.com/uri'));

        $client->soapCall('ping', array());
    }

    public function testWithHeaderProperlySetsHeadersInRequestBody()
    {
        $browser = $this->getMockBuilder('React\Http\Browser')->disableOriginalConstructor()->getMock();
        $browser->expects($this->once())->method('withRejectErrorResponse')->willReturnSelf();
        $browser->expects($this->once())->method('withFollowRedirects')->willReturnSelf();
        $browser->expects($this->once())->method('request')->with('POST', 'http://example.com')
            ->willReturnCallback(function ($method, $url, $headers, $body) {
                // We expect the SOAP header to be present on the request body
                $this->assertStringContainsString(
                    '<SOAP-ENV:Header><ns2:echoMeStringRequest>hello world</ns2:echoMeStringRequest></SOAP-ENV:Header>',
                    $body
                );
                return new Promise(function () { });
            });

        $client = new Client($browser, null, ['location' => 'http://example.com', 'uri' => 'http://example.com/uri']);
        $client = $client->withHeaders([new \SoapHeader(
            'http://soapinterop.org/echoheader/',
            'echoMeStringRequest',
            'hello world'
        )]);

        $client->soapCall('ping', []);
    }
}
