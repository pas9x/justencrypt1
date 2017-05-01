<?php

namespace pas9x\letsencrypt;

class CurlResponse
{
    protected $ch;
    protected $httpVersion;
    protected $responseCode;
    protected $responseMessage;
    protected $headers;
    protected $body;

    public function __construct($ch, $httpVersion, $responseCode, $responseMessage, $headers, $body)
    {
        $this->ch = $ch;
        $this->httpVersion = $httpVersion;
        $this->responseCode = $responseCode;
        $this->responseMessage = $responseMessage;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getCh()
    {
        return $this->ch;
    }

    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }

    public function getResponseMessage()
    {
        return $this->responseMessage;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeader($headerName) {
        return isset($this->headers[$headerName]) ? $this->headers[$headerName] : null;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getFullResponse()
    {
        $result = "{$this->httpVersion} {$this->responseCode} {$this->responseMessage}\n";
        foreach ($this->headers as $headerName => $headerValue) {
            $result .= "$headerName: $headerValue\n";
        }
        $result .= "\n\n";
        $result .= $this->body;
        return $result;
    }
}
