<?php

namespace pas9x\letsencrypt;

use \Exception;
use \stdClass;

class AcmeError extends Exception
{
    protected $response;

    public function __construct(stdClass $response)
    {
        if (!isset($response->type, $response->detail)) {
            throw new Exception('There is no type or detail key in this response');
        }
        $this->response = $response;
        $type = $this->getType();
        $type = preg_replace('/^urn\:acme\:error\:/', '', $type);
        $detail = trim($this->getDetail());
        $this->message = '[' . $type . ']';
        if ($detail !== '') {
            $this->message .= ' ' . $detail;
        }
    }

    public function getType()
    {
        return strval($this->response->type);
    }

    public function getDetail()
    {
        return strval($this->response->detail);
    }

    public function getStatus()
    {
        if (!isset($this->response->status)) {
            return null;
        }
        return intval($this->response->status);
    }
}
