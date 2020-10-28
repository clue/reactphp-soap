<?php

namespace Clue\Tests\React\Soap;

use Clue\React\Block;
use Clue\React\Soap\Client;
use Clue\React\Soap\Proxy;
use PHPUnit\Framework\TestCase;
use React\Http\Browser;

class BankResponse
{
}

/**
 * @group internet
 */
class FunctionalTest extends TestCase
{
    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var Client
     */
    private $client;

    // download WSDL file only once for all test cases
    private static $wsdl;
    /**
     * @beforeClass
     */
    public static function setUpFileBeforeClass()
    {
        self::$wsdl = file_get_contents('http://www.thomas-bayer.com/axis2/services/BLZService?wsdl');
    }

    /**
     * @before
     */
    public function setUpClient()
    {
        $this->loop = \React\EventLoop\Factory::create();
        $this->client = new Client(new Browser($this->loop), self::$wsdl);
    }

    public function testBlzService()
    {
        $this->assertCount(2, $this->client->getFunctions());
        $this->assertCount(3, $this->client->getTypes());

        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => '12070000'));

        $result = Block\await($promise, $this->loop);

        $this->assertIsObject($result);
        $this->assertTrue(isset($result->details));
        $this->assertTrue(isset($result->details->bic));
    }

    public function testBlzServiceWithClassmapReturnsExpectedType()
    {
        $this->client = new Client(new Browser($this->loop), self::$wsdl, array(
            'classmap' => array(
                'getBankResponseType' => 'Clue\Tests\React\Soap\BankResponse'
            )
        ));

        $this->assertCount(2, $this->client->getFunctions());
        $this->assertCount(3, $this->client->getTypes());

        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => '12070000'));

        $result = Block\await($promise, $this->loop);

        $this->assertInstanceOf('Clue\Tests\React\Soap\BankResponse', $result);
        $this->assertTrue(isset($result->details));
        $this->assertTrue(isset($result->details->bic));
    }

    public function testBlzServiceWithSoapV12()
    {
        $this->client = new Client(new Browser($this->loop), self::$wsdl, array(
            'soap_version' => SOAP_1_2
        ));

        $this->assertCount(2, $this->client->getFunctions());
        $this->assertCount(3, $this->client->getTypes());

        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => '12070000'));

        $result = Block\await($promise, $this->loop);

        $this->assertIsObject($result);
        $this->assertTrue(isset($result->details));
        $this->assertTrue(isset($result->details->bic));
    }

    public function testBlzServiceNonWsdlModeReturnedWithoutOuterResultStructure()
    {
        $this->client = new Client(new Browser($this->loop), null, array(
            'location' => 'http://www.thomas-bayer.com/axis2/services/BLZService',
            'uri' => 'http://thomas-bayer.com/blz/',
        ));

        $api = new Proxy($this->client);

        // try encoding the "blz" parameter with the correct namespace (see uri)
        // $promise = $api->getBank(new SoapParam('12070000', 'ns1:blz'));
        $promise = $api->getBank(new \SoapVar('12070000', XSD_STRING, null, null, 'blz', 'http://thomas-bayer.com/blz/'));

        $result = Block\await($promise, $this->loop);

        $this->assertIsObject($result);
        $this->assertFalse(isset($result->details));
        $this->assertTrue(isset($result->bic));
    }

    public function testBlzServiceWithRedirectLocationRejectsWithRuntimeException()
    {
        $this->client = new Client(new Browser($this->loop), null, array(
            'location' => 'http://httpbingo.org/redirect-to?url=' . rawurlencode('http://www.thomas-bayer.com/axis2/services/BLZService'),
            'uri' => 'http://thomas-bayer.com/blz/',
        ));

        $api = new Proxy($this->client);
        $promise = $api->getBank('a');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('redirects');
        Block\await($promise, $this->loop);
    }

    public function testBlzServiceWithInvalidBlzRejectsWithSoapFault()
    {
        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => 'invalid'));

        $this->expectException(\SoapFault::class);
        $this->expectExceptionMessage('Keine Bank zur BLZ invalid gefunden!');
        Block\await($promise, $this->loop);
    }

    public function testBlzServiceWithInvalidMethodRejectsWithSoapFault()
    {
        $api = new Proxy($this->client);

        $promise = $api->doesNotExist();

        $this->expectException(\SoapFault::class);
        $this->expectExceptionMessage('Function ("doesNotExist") is not a valid method for this service');
        Block\await($promise, $this->loop);
    }

    public function testCancelMethodRejectsWithRuntimeException()
    {
        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => '12070000'));
        $promise->cancel();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cancelled');
        Block\await($promise, $this->loop);
    }

    public function testTimeoutRejectsWithRuntimeException()
    {
        $browser = new Browser($this->loop);
        $browser = $browser->withTimeout(0);

        $this->client = new Client($browser, self::$wsdl);
        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => '12070000'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('timed out');
        Block\await($promise, $this->loop);
    }

    public function testGetLocationForFunctionName()
    {
        $this->assertEquals('http://www.thomas-bayer.com/axis2/services/BLZService', $this->client->getLocation('getBank'));
        $this->assertEquals('http://www.thomas-bayer.com/axis2/services/BLZService', $this->client->getLocation('getBank'));
    }

    public function testGetLocationForFunctionNumber()
    {
        $this->assertEquals('http://www.thomas-bayer.com/axis2/services/BLZService', $this->client->getLocation(0));
    }

    public function testGetLocationOfUnknownFunctionNameFails()
    {
        $this->expectException(\SoapFault::class);
        $this->client->getLocation('unknown');
    }

    public function testGetLocationForUnknownFunctionNumberFails()
    {
        $this->expectException(\SoapFault::class);
        $this->assertEquals('http://www.thomas-bayer.com/axis2/services/BLZService', $this->client->getLocation(100));
    }

    public function testGetLocationWithExplicitLocationOptionReturnsAsIs()
    {
        $this->client = new Client(new Browser($this->loop), self::$wsdl, array(
            'location' => 'http://example.com/'
        ));

        $this->assertEquals('http://example.com/', $this->client->getLocation(0));
    }

    public function testWithLocationReturnsUpdatedClient()
    {
        $original = $this->client->getLocation(0);
        $client = $this->client->withLocation('http://nonsense.invalid');

        $this->assertEquals('http://nonsense.invalid', $client->getLocation(0));
        $this->assertEquals($original, $this->client->getLocation(0));
    }

    public function testWithLocationInvalidRejectsWithRuntimeException()
    {
        $api = new Proxy($this->client->withLocation('http://nonsense.invalid'));

        $promise = $api->getBank(array('blz' => '12070000'));

        $this->expectException(\RuntimeException::class);
        Block\await($promise, $this->loop);
    }

    public function testWithLocationRestoredToOriginalResolves()
    {
        $original = $this->client->getLocation(0);
        $client = $this->client->withLocation('http://nonsense.invalid');
        $client = $client->withLocation($original);
        $api = new Proxy($client);

        $promise = $api->getBank(array('blz' => '12070000'));

        $result = Block\await($promise, $this->loop);
        $this->assertIsObject($result);
    }
}
