<?php

namespace Clue\React\Soap;

use Throwable;

class ClientException extends \SoapFault
{
    protected $request;
    protected $response;
    protected $method;

    /**
     * ClientException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param string|null $method
     * @param string|null $request
     * @param string|null $response
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
     *
     * @return ClientException
     */
    public function setRequest($request)
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
     *
     * @return ClientException
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return null
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param null $method
     *
     * @return ClientException
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }
}
