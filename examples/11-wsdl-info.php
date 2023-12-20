<?php

require __DIR__ . '/../vendor/autoload.php';

$browser = new React\Http\Browser();

$wsdl = isset($argv[1]) ? $argv[1] : 'http://www.thomas-bayer.com/axis2/services/BLZService?wsdl';

$browser->get($wsdl)->done(
    function (Psr\Http\Message\ResponseInterface $response) use ($browser) {
        $client = new Clue\React\Soap\Client($browser, (string)$response->getBody());

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
