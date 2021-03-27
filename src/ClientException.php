<?php

namespace Clue\React\Soap;

use Throwable;

class ClientException extends \SoapFault
{
    /** @var mixed */
    protected $request;

    /** @var mixed */
    protected $response;

    /** @var string|null */
    protected $method;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param string|null $method
     * @param object|string|null $request
     * @param object|string|null $response
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null, $method = null, $request = null, $response = null)
    {
        parent::__construct($message, $code, $previous);

        $this->message = $message;

        if ($previous instanceof \SoapFault) {
            $this->faultcode = isset($previous->faultcode) ? $previous->faultcode : null;
            $this->faultstring = isset($previous->faultstring) ? $previous->faultstring : null;
            $this->faultactor = isset($previous->faultactor) ? $previous->faultactor : null;
            $this->detail = isset($previous->detail) ? $previous->detail : null;
            $this->faultname = isset($previous->faultactor) ? $previous->faultname : null;
        }

        $this->method = $method;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request): ClientException
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response): ClientException
    {
        $this->response = $response;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): ClientException
    {
        $this->method = $method;

        return $this;
    }
}
