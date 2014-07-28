<?php

use Clue\React\Soap\Factory;
use Clue\React\Soap\Proxy;
use Clue\React\Soap\Client;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);

$wsdl = isset($argv[1]) ? $argv[1] : 'http://www.thomas-bayer.com/axis2/services/BLZService?wsdl';

$factory->createClient($wsdl)->then(
    function (Client $client) {
        echo 'Functions:' . PHP_EOL .
             implode(PHP_EOL, $client->getFunctions()) . PHP_EOL .
             PHP_EOL .
             'Types:' . PHP_EOL .
             implode(PHP_EOL, $client->getTypes()) . PHP_EOL;
    },
    function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    }
);

$loop->run();
