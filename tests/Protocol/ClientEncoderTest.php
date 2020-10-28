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
        $this->assertSame('demo#add', $request->getHeaderLine('SOAPAction'));
    }

    public function testEncodeCreatesRequestForNonWsdlRpcFunctionWithSoapV12()
    {
        $encoder = new ClientEncoder(null, array(
            'location' => 'http://example.com/soap',
            'uri' => 'demo',
            'soap_version' => SOAP_1_2
        ));

        $request = $encoder->encode('add', array('first' => 10, 'second' => 20));

        $this->assertTrue($request instanceof RequestInterface);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('http://example.com/soap', (string)$request->getUri());
        $this->assertSame('application/soap+xml; charset=utf-8; action=demo#add', $request->getHeaderLine('Content-Type'));
        $this->assertFalse($request->hasHeader('SOAPAction'));
    }

    public function testConstructorThrowsWhenUrlIsInvalid()
    {
        if (extension_loaded('xdebug')) {
            $this->markTestSkipped('Invalid WSDL causes a fatal error when ext-xdebug is loaded');
        }

        $this->setExpectedException('SoapFault');
        new ClientEncoder('invalid');
    }

    public function testConstructorThrowsWhenNonWsdlDoesNotDefineLocationAndUri()
    {
        if (extension_loaded('xdebug')) {
            $this->markTestSkipped('Invalid non-WSDL mode causes a fatal error when ext-xdebug is loaded');
        }

        $this->setExpectedException('SoapFault');
        new ClientEncoder(null);
    }

    public function testEncodeRequestForBlzServiceNonWsdlMode()
    {
        $encoder = new ClientEncoder(null, array(
            'location' => 'http://www.thomas-bayer.com/axis2/services/BLZService',
            'uri' => 'http://thomas-bayer.com/blz/',
            'use' => SOAP_LITERAL
        ));

        // try encoding the "blz" parameter with the correct namespace (see uri)
        // $request = $encoder->encode('getBank', array(new SoapParam('12070000', 'ns1:blz')));
        $request = $encoder->encode('getBank', array(
            new SoapVar('12070000', XSD_STRING, null, null, 'blz', 'http://thomas-bayer.com/blz/'),
        ));

        $expected = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://thomas-bayer.com/blz/"><SOAP-ENV:Body><ns1:getBank><ns1:blz>12070000</ns1:blz></ns1:getBank></SOAP-ENV:Body></SOAP-ENV:Envelope>
';

        $this->assertEquals($expected, (string)$request->getBody());
    }

    public function setExpectedException($exception, $exceptionMessage = '', $exceptionCode = null)
    {
        if (method_exists($this, 'expectException')) {
            // PHPUnit 5+
            $this->expectException($exception);
            if ($exceptionMessage !== '') {
                $this->expectExceptionMessage($exceptionMessage);
            }
            if ($exceptionCode !== null) {
                $this->expectExceptionCode($exceptionCode);
            }
        } else {
            // legacy PHPUnit 4
            parent::setExpectedException($exception, $exceptionMessage, $exceptionCode);
        }
    }
}
