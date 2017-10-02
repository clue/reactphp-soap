# Changelog

## 0.2.0 (2017-10-02)

*   Feature: Added the possibility to use local WSDL files
    (#11 by @floriansimon1)

    ```php
    $factory = new Factory($loop);
    $wsdl = file_get_contents('service.wsdl');
    $client = $factory->createClientFromWsdl($wsdl);
    ```

*   Feature: Add `Client::getLocation()` helper
    (#13 by @clue)

*   Feature: Forward compatibility with clue/buzz-react v2.0 and upcoming EventLoop
    (#9 by floriansimon1 and #19 and #21 by @clue)

*   Improve test suite by adding PHPUnit to require-dev and
    test PHP 5.3 through PHP 7.0 and HHVM and
    fix Travis build config
    (#1 by @WyriHaximus and #12, #17 and #22 by @clue)

## 0.1.0 (2014-07-28)

* First tagged release

## 0.0.0 (2014-07-20)

* Initial concept
