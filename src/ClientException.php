<?php
/**
 * Created by PhpStorm.
 * User: fer
 * Date: 2019-01-30
 * Time: 15:43
 */

namespace Clue\React\Soap;

use Throwable;

class ClientException extends \SoapFault
{
    protected $request;
    protected $response;
    protected $method;

    public function __construct($message = "", $code = 0, Throwable $previous = null, $method = null, $request = null, $response = null)
    {
        parent::__construct($message, $code, $previous);

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
}