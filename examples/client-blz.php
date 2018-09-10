<?php

use Clue\React\Buzz\Browser;
use Clue\React\Soap\Client;
use Clue\React\Soap\Proxy;
use Psr\Http\Message\ResponseInterface;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$browser = new Browser($loop);

$blz = isset($argv[1]) ? $argv[1] : '12070000';

$browser->get('http://www.thomas-bayer.com/axis2/services/BLZService?wsdl')->done(function (ResponseInterface $response) use ($browser, $blz) {
    $client = new Client($browser, (string)$response->getBody());
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
