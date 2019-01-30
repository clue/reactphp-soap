<?php

namespace Clue\React\Soap;

class SoapResponse
{
    /** @var string|null */
    private $response;

    /** @var string|null */
    private $request;

    /** @var string|null */
    private $content;

    /** @var string|null */
    private $method;

    /** @var mixed[] */
    private $params;

    /**
     * @return string|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string|null $response
     *
     * @return SoapResponse
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string|null $request
     *
     * @return SoapResponse
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     *
     * @return SoapResponse
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string|null $method
     *
     * @return SoapResponse
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed[] $params
     *
     * @return SoapResponse
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }
}
