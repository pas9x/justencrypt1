<?php

namespace pas9x\letsencrypt;

use \Exception;
use \stdClass;

class UnexpectedResponse extends Exception {
    protected $response;
    protected $note;

    public function __construct(CurlResponse $response, $note = null) {
        $this->response = $response;
        $this->note = $note;
        $this->message = 'Unexpected response from ACME server.';
        if (is_string($note)) {
            $this->message .= ' note=' . $note;
        }
    }

    public function getResponse() {
        return $this->response;
    }

    public function getNote() {
        return $this->note;
    }

    public function __toString()
    {
        $result = trim(parent::__toString()) . "\n";
        $result .= "ACME server response:\n";
        $result .= trim($this->response->getFullResponse()) . "\n";
        return $result;
    }
}