<?php

use PHPUnit\Framework\TestCase;
use Clue\React\Soap\Protocol\ClientEncoder;
use Psr\Http\Message\RequestInterface;

class ClientEncoderTest extends TestCase
{
    public function testEncodeCreatesRequestForNonWsdlRpcFunction()
    {
        $encoder = new ClientEncoder(null, array('location' => 'http://example.com/soap', 'uri' => 'demo'));

        $request = $encoder->encode('add', array('first' => 10, 'second' => 20));

        $this->assertTrue($request instanceof RequestInterface);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('http://example.com/soap', (string)$request->getUri());
        $this->assertSame('text/xml; charset=utf-8', $request->getHeaderLine('Content-Type'));
    }

    /**
     * @expectedException SoapFault
     */
    public function testConstructorThrowsWhenUrlIsInvalid()
    {
        if (extension_loaded('xdebug')) {
            $this->markTestSkipped('Invalid WSDL causes a fatal error when ext-xdebug is loaded');
        }

        new ClientEncoder('invalid');
    }

    /**
     * @expectedException SoapFault
     */
    public function testConstructorThrowsWhenNonWsdlDoesNotDefineLocationAndUri()
    {
        if (extension_loaded('xdebug')) {
            $this->markTestSkipped('Invalid non-WSDL mode causes a fatal error when ext-xdebug is loaded');
        }

        new ClientEncoder(null);
    }
}
