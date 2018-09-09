<?php

namespace Clue\React\Soap;

use Clue\React\Buzz\Browser;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * The `Factory` class is responsible for fetching the WSDL file once and constructing
 * the [`Client`](#client) instance.
 * It also registers everything with the main [`EventLoop`](https://github.com/reactphp/event-loop#usage).
 *
 * ```php
 * $loop = React\EventLoop\Factory::create();
 * $factory = new Factory($loop);
 * ```
 *
 * If you need custom DNS or proxy settings, you can explicitly pass a
 * custom [`Browser`](https://github.com/clue/php-buzz-react#browser) instance:
 *
 * ```php
 * $browser = new Clue\React\Buzz\Browser($loop);
 * $factory = new Factory($loop, $browser);
 * ```
 */
final class Factory
{
    private $loop;
    private $browser;

    public function __construct(LoopInterface $loop, Browser $browser = null)
    {
        if ($browser === null) {
            $browser = new Browser($loop);
        }
        $this->loop = $loop;
        $this->browser = $browser;
    }

    /**
     * Downloads the WSDL at the given URL into memory and create a new [`Client`](#client).
     *
     * ```php
     * $factory->createClient($url)->then(
     *     function (Client $client) {
     *         // client ready
     *     },
     *     function (Exception $e) {
     *         // an error occured while trying to download or parse the WSDL
     *     }
     * );
     * ```
     *
     * @param string $wsdl
     * @return PromiseInterface Returns a Promise<Client, Exception>
     */
    public function createClient($wsdl)
    {
        $that = $this;

        return $this->browser->get($wsdl)->then(function (ResponseInterface $response) use ($that) {
            return $that->createClientFromWsdl((string)$response->getBody());
        });
    }

    /**
     * Creates a new [`Client`](#client) from the given WSDL contents.
     *
     * This works similar to `createClient()`, but leaves you the responsibility to load
     * the WSDL file. This allows you to use local WSDL files, for instance.
     *
     * @param string $wsdlContents
     * @return Client
     */
    public function createClientFromWsdl($wsdlContents)
    {
        $browser = $this->browser;
        $url     = 'data://text/plain;base64,' . base64_encode($wsdlContents);

        return new Client($url, $browser);
    }
}
