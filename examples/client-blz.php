<?php

use Clue\React\Soap\Factory;
use Clue\React\Soap\Proxy;
use Clue\React\Soap\Client;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);

$blz = isset($argv[1]) ? $argv[1] : '12070000';

$factory->createClient('http://www.thomas-bayer.com/axis2/services/BLZService?wsdl')->then(function (Client $client) use ($blz) {
    //var_dump($client->getFunctions(), $client->getTypes());

    $api = new Proxy($client);

    $api->getBank(array('blz' => $blz))->then(
        function ($result) {
            echo 'SUCCESS!' . PHP_EOL;
            var_dump($result);
        },
        function (Exception $e) {
            echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
        }
    );
});

$loop->run();
