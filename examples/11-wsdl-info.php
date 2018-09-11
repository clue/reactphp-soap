<?php

use Clue\React\Buzz\Browser;
use Clue\React\Soap\Client;
use Psr\Http\Message\ResponseInterface;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$browser = new Browser($loop);

$wsdl = isset($argv[1]) ? $argv[1] : 'http://www.thomas-bayer.com/axis2/services/BLZService?wsdl';

$browser->get($wsdl)->done(
    function (ResponseInterface $response) use ($browser) {
        $client = new Client($browser, (string)$response->getBody());

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
