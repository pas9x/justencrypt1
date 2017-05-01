<?php

namespace pas9x\letsencrypt;

use \Exception;

class CurlRequest
{
    public $ignoreInvalidSSL = true;

    public static function curlExec($ch)
    {
        $rawHeaders = [];
        $callback = function($ch, $header)  use(&$rawHeaders)
        {
            $header = strval($header);
            $rawHeaders[] = $header;
            $result = strlen($header);
            return $result;
        };
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, $callback);
        $body = curl_exec($ch);
        if (count($rawHeaders) < 1) {
            throw new Exception('cURL request failed: ' . curl_error($ch));
        }
        $startString = $rawHeaders[0];
        $startString = trim($startString);
        $startString = preg_split('/\s+/', $startString, 3);
        if (count($startString) < 2) {
            throw new Exception('Invalid response: bad start string');
        }
        $httpVersion = $startString[0];
        $responseCode = $startString[1];
        $responseMessage = isset($startString[2]) ? $startString[2]: null;

        if (!preg_match('/^[0-9]{1,3}$/', $responseCode)) {
            throw new Exception('Invalid response: bad response code');
        }
        $responseCode = intval($responseCode);
        $headers = [];
        unset($rawHeaders[0]);
        foreach ($rawHeaders as $rawHeader) {
            $split = trim($rawHeader);
            $split = explode(':', $split, 2);
            $headerName = trim(strtolower($split[0]));
            $headerValue = isset($split[1]) ? trim($split[1]) : null;
            if ($headerName !== '') {
                $headers[$headerName] = $headerValue;
            }
        }
        $result = new CurlResponse($ch, $httpVersion, $responseCode, $responseMessage, $headers, $body);
        return $result;
    }

    protected static function httpRequestStatic($url, array $curlOptions = [])
    {
        $ch = curl_init($url);
        if (!isset($curlOptions[CURLOPT_RETURNTRANSFER])) {
            $curlOptions[CURLOPT_RETURNTRANSFER] = 1;
        }
        curl_setopt_array($ch, $curlOptions);
        $result = self::curlExec($ch);
        return $result;
    }

    protected function httpRequest($url, array $curlOptions = [])
    {
        if ($this->ignoreInvalidSSL) {
            if (!isset($curlOptions[CURLOPT_SSL_VERIFYHOST])) {
                $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
            }
            if (!isset($curlOptions[CURLOPT_SSL_VERIFYPEER])) {
                $curlOptions[CURLOPT_SSL_VERIFYPEER] = 0;
            }
        }
        $result = self::httpRequestStatic($url, $curlOptions);
        return $result;
    }

    public static function httpGetStatic($url, array $curlOptions = [])
    {
        $result = self::httpRequest($url, $curlOptions);
        return $result;
    }

    public function httpGet($url, array $curlOptions = [])
    {
        $result = $this->httpRequest($url, $curlOptions);
        return $result;
    }

    public static function httpPostStatic($url, $postdata = null, array $curlOptions = [])
    {
        if (is_string($postdata) || is_array($postdata)) {
            $curlOptions[CURLOPT_POSTFIELDS] = $postdata;
        }
        elseif (!is_null($postdata)) {
            throw new Exception('Invalid type of $postdata argument: ' . gettype($postdata));
        }
        if (!isset($curlOptions[CURLOPT_POST])) {
            $curlOptions[CURLOPT_POST] = 1;
        }
        $result = self::httpRequest($url, $curlOptions);
        return $result;
    }

    public function httpPost($url, $postdata = null, array $curlOptions = [])
    {
        if (is_string($postdata) || is_array($postdata)) {
            $curlOptions[CURLOPT_POSTFIELDS] = $postdata;
        }
        elseif (!is_null($postdata)) {
            throw new Exception('Invalid type of $postdata argument: ' . gettype($postdata));
        }
        if (!isset($curlOptions[CURLOPT_POST])) {
            $curlOptions[CURLOPT_POST] = 1;
        }
        $result = $this->httpRequest($url, $curlOptions);
        return $result;
    }
}
