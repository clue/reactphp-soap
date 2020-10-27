# Changelog

## 2.0.0 (2020-10-28)

*   Feature / BC break: Update to reactphp/http v1.0.0.
    (#45 by @SimonFrings)

*   Feature / BC break: Add type declarations and require PHP 7.1+ as a consequence
    (#47 by @SimonFrings, #49 by @clue)

*   Use fully qualified class names in documentation.
    (#46 by @SimonFrings)

*   Improve test suite and add `.gitattributes` to exclude dev files from export.
    Prepare PHP 8 support, update to PHPUnit 9 and simplify test matrix.
    (#40 by @andreybolonin, #42 and #44 by @SimonFrings and #48 by @clue)

## 1.0.0 (2018-11-07)

*   First stable release, now following SemVer!

    I'd like to thank [Bergfreunde GmbH](https://www.bergfreunde.de/), a German-based
    online retailer for Outdoor Gear & Clothing, for sponsoring large parts of this development! 🎉
    Thanks to sponsors like this, who understand the importance of open source
    development, I can justify spending time and focus on open source development
    instead of traditional paid work.

    > Did you know that I offer custom development services and issuing invoices for
      sponsorships of releases and for contributions? Contact me (@clue) for details.

*   BC break / Feature: Replace `Factory` with simplified `Client` constructor,
    add support for optional SOAP options and non-WSDL mode and
    respect WSDL type definitions when decoding and support classmap option.
    (#31, #32 and #33 by @clue)

    ```php
    // old
    $factory = new Factory($loop);
    $client = $factory->createClientFromWsdl($wsdl);

    // new
    $browser = new Browser($loop);
    $client = new Client($browser, $wsdl);
    ```

    The `Client` constructor now accepts an array of options. All given options will
    be passed through to the underlying `SoapClient`. However, not all options
    make sense in this async implementation and as such may not have the desired
    effect. See also [`SoapClient`](http://php.net/manual/en/soapclient.soapclient.php)
    documentation for more details.

    If working in WSDL mode, the `$options` parameter is optional. If working in
    non-WSDL mode, the WSDL parameter must be set to `null` and the options
    parameter must contain the `location` and `uri` options, where `location` is
    the URL of the SOAP server to send the request to, and `uri` is the target
    namespace of the SOAP service:

    ```php
    $client = new Client($browser, null, array(
        'location' => 'http://example.com',
        'uri' => 'http://ping.example.com',
    ));
    ```

*   BC break: Mark all classes as final and all internal APIs as `@internal`.
    (#26 and #37 by @clue)

*   Feature: Add new `Client::withLocation()` method.
    (#38 by @floriansimon1, @pascal-hofmann and @clue)

    The `withLocation(string $location): self` method can be used to
    return a new `Client` with the updated location (URI) for all functions.

    Note that this is not to be confused with the WSDL file location.
    A WSDL file can contain any number of function definitions.
    It's very common that all of these functions use the same location definition.
    However, technically each function can potentially use a different location.

    ```php
    $client = $client->withLocation('http://example.com/soap');

    assert('http://example.com/soap' === $client->getLocation('echo'));
    ```

    As an alternative to this method, you can also set the `location` option
    in the `Client` constructor (such as when in non-WSDL mode).

*   Feature: Properly handle SOAP error responses, accept HTTP error responses and do not follow any HTTP redirects.
    (#35 by @clue)

*   Improve documentation and update project homepage,
    documentation for HTTP proxy servers,
    support timeouts for SOAP requests (HTTP timeout option) and
    add cancellation support.
    (#25, #29, #30 #34 and #36 by @clue)

*   Improve test suite by supporting PHPUnit 6,
    optionally skip functional integration tests requiring internet and
    test against PHP 7.2 and PHP 7.1 and latest ReactPHP components.
    (#24 by @carusogabriel and #27 and #28 by @clue)

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
    (#9 by @floriansimon1 and #19 and #21 by @clue)

*   Improve test suite by adding PHPUnit to require-dev and
    test PHP 5.3 through PHP 7.0 and HHVM and
    fix Travis build config
    (#1 by @WyriHaximus and #12, #17 and #22 by @clue)

## 0.1.0 (2014-07-28)

* First tagged release

## 0.0.0 (2014-07-20)

* Initial concept
