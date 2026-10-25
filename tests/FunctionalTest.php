<?php

namespace Clue\Tests\React\Soap;

use Clue\React\Block;
use Clue\React\Soap\Client;
use Clue\React\Soap\Proxy;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;
use React\Http\Browser;

class AddResponse
{
    public readonly int $additionResult;
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

    // set up server once for all test cases
    private static $serverProcess;

    // download WSDL file only once for all test cases
    private static $wsdl;

    /**
     * @beforeClass
     */
    public static function setUpServerBeforeClass()
    {
        $listenAddress = '127.0.0.1:8000';
        $serverRoot = __DIR__;
        $serverStartCommand = sprintf('php -S %s -t %s >/dev/null 2>&1 &', $listenAddress, $serverRoot);
        self::$serverProcess = \proc_open($serverStartCommand, [], $pipes);

        // Briefly wait for the server to settle.
        sleep(1);
    }

    public static function tearDownAfterClass(): void
    {
        proc_close(self::$serverProcess);
    }

    /**
     * @beforeClass
     */
    public static function setUpFileBeforeClass()
    {
        self::$wsdl = file_get_contents('http://localhost:8000/SimpleSoapServer.php?wsdl');
    }

    /**
     * @before
     */
    public function setUpClient()
    {
        $this->client = new Client(null, self::$wsdl);
    }

    public function testSoapService()
    {
        $this->assertCount(1, $this->client->getFunctions());
        $this->assertCount(2, $this->client->getTypes());

        $api = new Proxy($this->client);

        $promise = $api->add(['x' => 21, 'y' => 21]);

        $result = Block\await($promise, Loop::get());

        $this->assertIsObject($result);
        $this->assertEquals(42, $result->additionResult);
    }

    public function testSoapServiceWithClassmapReturnsExpectedType()
    {
        $this->client = new Client(null, self::$wsdl, array(
            'classmap' => array(
                'AddResponse' => AddResponse::class
            )
        ));

        $this->assertCount(1, $this->client->getFunctions());
        $this->assertCount(2, $this->client->getTypes());

        $api = new Proxy($this->client);

        $promise = $api->add(['x' => 21, 'y' => 21]);

        $result = Block\await($promise, Loop::get());

        $this->assertInstanceOf(AddResponse::class, $result);
        $this->assertEquals(42, $result->additionResult);
    }

    public function testSoapServiceWithSoapV12()
    {
        $this->client = new Client(null, self::$wsdl, array(
            'soap_version' => SOAP_1_2
        ));

        $this->assertCount(1, $this->client->getFunctions());
        $this->assertCount(2, $this->client->getTypes());

        $api = new Proxy($this->client);

        $promise = $api->add(['x' => 21, 'y' => 21]);

        $result = Block\await($promise, Loop::get());

        $this->assertIsObject($result);
        $this->assertEquals(42, $result->additionResult);
    }

    public function testSoapServiceNonWsdlMode()
    {
        $this->client = new Client(null, null, array(
            'location' => 'http://localhost:8000/SimpleSoapServer.php',
            'uri' => 'http://localhost:8000/SimpleSoapServer.php'
        ));

        $api = new Proxy($this->client);
        
        $promise = $api->add((object)['x' => 21, 'y' => 21]);

        $result = Block\await($promise, Loop::get());

        $this->assertEquals(42, $result->additionResult);
    }
    public function testSoapServiceWithRedirectLocationRejectsWithRuntimeException()
    {
        $this->client = new Client(null, null, array(
            'location' => 'http://httpbin.org/redirect-to?url=' . rawurlencode('http://localhost:8000/SimpleSoapServer.php/'),
            'uri' => 'http://localhost:8000/SimpleSoapServer.php/',
        ));

        $api = new Proxy($this->client);
        $promise = $api->add(['x' => 21, 'y' => 21]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('redirects');
        Async\await($promise);
    }
    public function testSoapServiceWithInvalidMethodRejectsWithSoapFault()
    {
        $api = new Proxy($this->client);

        $promise = $api->doesNotExist();

        $this->expectException(\SoapFault::class);
        $this->expectExceptionMessage('Function ("doesNotExist") is not a valid method for this service');
        Block\await($promise, Loop::get());
    }

    public function testCancelMethodRejectsWithRuntimeException()
    {
        $api = new Proxy($this->client);

        $promise = $api->add(['x' => 21, 'y' => 21]);
        $promise->cancel();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cancelled');
        Block\await($promise, Loop::get());
    }

    public function testTimeoutRejectsWithRuntimeException()
    {
        $browser = new Browser();
        $browser = $browser->withTimeout(0);

        $this->client = new Client($browser, self::$wsdl);
        $api = new Proxy($this->client);

        $promise = $api->add(['x' => 21, 'y' => 21]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('timed out');
        Block\await($promise, Loop::get());
    }

    public function testGetLocationForFunctionName()
    {
        $this->assertEquals('http://localhost:8000/SimpleSoapServer.php', $this->client->getLocation('add'));
        $this->assertEquals('http://localhost:8000/SimpleSoapServer.php', $this->client->getLocation('add'));
    }

    public function testGetLocationForFunctionNumber()
    {
        $this->assertEquals('http://localhost:8000/SimpleSoapServer.php', $this->client->getLocation(0));
    }

    public function testGetLocationOfUnknownFunctionNameFails()
    {
        $this->expectException(\SoapFault::class);
        $this->client->getLocation('unknown');
    }

    public function testGetLocationForUnknownFunctionNumberFails()
    {
        $this->expectException(\SoapFault::class);
        $this->assertEquals('http://localhost:8000/SimpleSoapServer.php', $this->client->getLocation(100));
    }

    public function testGetLocationWithExplicitLocationOptionReturnsAsIs()
    {
        $this->client = new Client(null, self::$wsdl, array(
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

        $promise = $api->add(['x' => 21, 'y' => 21]);

        $this->expectException(\RuntimeException::class);
        Block\await($promise, Loop::get());
    }

    public function testWithLocationRestoredToOriginalResolves()
    {
        $original = $this->client->getLocation(0);
        $client = $this->client->withLocation('http://nonsense.invalid');
        $client = $client->withLocation($original);
        $api = new Proxy($client);

        $promise = $api->add(['x' => 21, 'y' => 21]);

        $result = Block\await($promise, Loop::get());
        $this->assertIsObject($result);
        $this->assertEquals(42, $result->additionResult);
    }
}
