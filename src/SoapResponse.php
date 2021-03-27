<?php

namespace Clue\React\Soap;

class SoapResponse
{
    /** @var string|null */
    private $response;

    /** @var string|null */
    private $request;

    /** @var string|object|null */
    private $content;

    /** @var string|null */
    private $method;

    /** @var mixed[] */
    private $params;

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): SoapResponse
    {
        $this->response = $response;

        return $this;
    }

    public function getRequest(): ?string
    {
        return $this->request;
    }

    public function setRequest(?string $request): SoapResponse
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return string|null|object
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string|object|null $content
     */
    public function setContent($content): SoapResponse
    {
        $this->content = $content;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): SoapResponse
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
     */
    public function setParams($params): SoapResponse
    {
        $this->params = $params;

        return $this;
    }
}
