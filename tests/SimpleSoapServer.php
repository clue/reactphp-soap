<?php
declare(strict_types=1);
ini_set("soap.wsdl_cache_enabled","0");

class AdditionResult {
    public function __construct(public readonly int $additionResult) {}
}

class SoapService {
    public function add(object $intsToSum) {
        return new AdditionResult($intsToSum->x + $intsToSum->y);
    }
}

// Create SOAP server with WSDL
$server = new SoapServer(__DIR__ . '/SimpleSoapServer.wsdl', array(
    'soap_version' => SOAP_1_2,
    'trace' => true
));

// Set the class that handles the SOAP requests
$server->setClass('SoapService');

// Handle the request
$server->handle(); 
