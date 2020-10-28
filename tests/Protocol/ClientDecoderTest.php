<?php

use Clue\React\Soap\Protocol\ClientDecoder;
use PHPUnit\Framework\TestCase;

class ClientDecoderTest extends TestCase
{
    public function testDecodeThrowsSoapFaultForInvalidResponse()
    {
        $decoder = new ClientDecoder(null, array('location' => '1', 'uri' => '2'));

        $this->expectException(\SoapFault::class);
        $decoder->decode('anything', 'invalid');
    }

    public function testDecodeMessageToObjectNonWsdl()
    {
        $decoder = new ClientDecoder(null, array('location' => '1', 'uri' => '2'));

        $res = $decoder->decode('anything', <<<SOAP
<?xml version='1.0' encoding='utf-8'?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><ns1:getBankResponse xmlns:ns1="http://thomas-bayer.com/blz/"><ns1:details><ns1:bezeichnung>Deutsche Bank Ld Brandenburg</ns1:bezeichnung><ns1:bic>DEUTDEBB160</ns1:bic><ns1:ort>Potsdam</ns1:ort><ns1:plz>14405</ns1:plz></ns1:details></ns1:getBankResponse></soapenv:Body></soapenv:Envelope>
SOAP
        );

        $expected = new stdClass();
        $expected->bezeichnung = 'Deutsche Bank Ld Brandenburg';
        $expected->bic = 'DEUTDEBB160';
        $expected->ort = 'Potsdam';
        $expected->plz = '14405';

        $this->assertEquals($expected, $res);
    }
}
