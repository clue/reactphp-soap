<?php

use Clue\React\Soap\Factory;
use Clue\React\Soap\Client;
use Clue\React\Soap\Proxy;

class FunctionalTest extends TestCase
{
    private $loop;
    private $client;

    public function setUp()
    {
        $this->loop = React\EventLoop\Factory::create();
        $factory = new Factory($this->loop);

        $promise = $factory->createClient('http://www.thomas-bayer.com/axis2/services/BLZService?wsdl');

        $this->expectPromiseResolve($promise);
        $this->client = $this->waitForPromise($promise, $this->loop);
        /* @var $client Client */
    }

    public function testBlzService()
    {
        $this->assertCount(2, $this->client->getFunctions());
        $this->assertCount(3, $this->client->getTypes());

        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => '12070000'));

        $this->expectPromiseResolve($promise);
        $result = $this->waitForPromise($promise, $this->loop);

        $this->assertInternalType('object', $result);
    }

    public function testBlzServiceWithInvalidBlz()
    {
        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => 'invalid'));

        $this->expectPromiseReject($promise);

        $this->setExpectedException('Exception');
        $this->waitForPromise($promise, $this->loop);
    }

    public function testBlzServiceWithInvalidMethod()
    {
        $api = new Proxy($this->client);

        $promise = $api->doesNotexist();

        $this->expectPromiseReject($promise);

        $this->setExpectedException('Exception');
        $this->waitForPromise($promise, $this->loop);
    }

    public function testWrongLocationOverride()
    {
        $this->client->withTarget('nonsense.not.existing');
        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => '12070000'));

        $this->expectPromiseReject($promise);

        $this->setExpectedException('Exception');
        $this->waitForPromise($promise, $this->loop);
    }

    public function testCorrectLocationOverride()
    {
        $this->client->withTarget('nonsense.not.existing');
        $this->client->withTarget('http://www.thomas-bayer.com/axis2/services/BLZService');
        $this->testBlzService();
    }

    public function testGetLocation()
    {
        $this->assertEquals(
            $this->client->getWsdlTarget(),
            'http://www.thomas-bayer.com/axis2/services/BLZService'
        );
    }
}
