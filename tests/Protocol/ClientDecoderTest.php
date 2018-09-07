<?php

use Clue\React\Soap\Protocol\ClientDecoder;
use PHPUnit\Framework\TestCase;

class ClientDecoderTest extends TestCase
{
    /**
     * @expectedException SoapFault
     */
    public function testDecodeThrowsSoapFaultForInvalidResponse()
    {
        $decoder = new ClientDecoder();
        $decoder->decode('invalid');
    }
}
