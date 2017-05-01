<?php

namespace pas9x\letsencrypt;

use \Exception;
use \InvalidArgumentException;

class LetsEncrypt
{
    protected $apiHost = 'acme-v01.api.letsencrypt.org';
    protected $accountPrivateKey = null;
    protected $accountKeyInfo = [];
    protected $lastNonce = null;
    /**
     * @var CurlResponse lastResponse
     */
    protected $lastResponse = null;
    public $ignoreInvalidSSL = false;

    public function __construct($privateKey, $privateKeyPass = null, $apiHost = null)
    {
        if (is_resource($privateKey)) {
            $resourceType = get_resource_type($privateKey);
            if ($resourceType !== 'OpenSSL key') {
                throw new Exception('$privateKey argument has incorrect resource type: ' . $resourceType);
            }
            $this->accountPrivateKey = $privateKey;
        } else {
            $this->accountPrivateKey = self::readPrivateKey($privateKey, $privateKeyPass);
        }
        if (!is_null($apiHost)) {
            $this->apiHost = $apiHost;
        }
        $this->accountKeyInfo = openssl_pkey_get_details($this->accountPrivateKey);
    }

    public static function generatePrivateKey($returnAsText = false, $password = null)
    {
        $keyHandle = openssl_pkey_new(['private_key_bits' => 4096]);
        if (!is_resource($keyHandle)) {
            throw new Exception("openssl_pkey_new() function failed: " . openssl_error_string());
        }
        if (!$returnAsText) {
            return $keyHandle;
        }
        $result = null;
        openssl_pkey_export($keyHandle, $result, $password);
        return $result;
    }

    public static function generateCSR(
        $privateKey,
        $domainName,
        $emailAddress,
        $countryCode,
        $stateOrProvinceName,
        $localityName,
        $organizationName,
        $organizationalUnitName
    )
    {
        $distinguishedName = [
            'countryName' => $countryCode,
            'stateOrProvinceName' => $stateOrProvinceName,
            'localityName' => $localityName,
            'organizationName' => $organizationName,
            'organizationalUnitName' => $organizationalUnitName,
            'commonName' => $domainName,
            'emailAddress' => $emailAddress,
        ];
        if (is_resource($privateKey)) {
            $resourceType = get_resource_type($privateKey);
            if ($resourceType !== 'OpenSSL key') {
                throw new Exception('Invalid resource type of $privateKey argument: ' . $resourceType);
            }
        }
        elseif (is_string($privateKey)) {
            $privateKey = self::readPrivateKey($privateKey);
        }
        else {
            throw new Exception('Invalid type of $privateKey argument: ' . gettype($privateKey));
        }
        $csr = @openssl_csr_new($distinguishedName, $privateKey, ['digest_alg' => 'sha1']);
        if (!is_resource($csr)) {
            $msg = 'OpenSSL failed to generate CSR: ' . openssl_error_string();
            throw new Exception($msg);
        }
        $result = '';
        $ok = @openssl_csr_export($csr, $result, true);
        if (!$ok) {
            $msg = 'OpenSSL failed to export CSR: ' . openssl_error_string();
            throw new Exception($msg);
        }
        return trim($result);
    }

    public function registerAccount(array $contacts)
    {
        $url = $this->url('/acme/new-reg');
        $arguments = [
            'resource' => 'new-reg',
            'contact' => $contacts
        ];
        $resp = $this->signedPost($url, $arguments);
        $resp = json_decode($resp);
        $this->checkForError($resp);
        $accountId = isset($resp->id) ? intval($resp->id) : null;
        $agreementLink = self::extractLink($this->lastResponse->getHeader('link'));
        if (is_null($agreementLink)) {
            throw new UnexpectedResponse($this->lastResponse, 'agreement link not found');
        }
        $confirmLink = $this->lastResponse->getHeader('location');
        if (is_null($confirmLink)) {
            throw new UnexpectedResponse($this->lastResponse, 'no location header');
        }
        if (!is_int($accountId)) {
            throw new UnexpectedResponse($this->lastResponse, 'account id not found');
        }
        $arguments = [
            'resource' => 'reg',
            'agreement' => $agreementLink
        ];
        $resp = $this->signedPost($confirmLink, $arguments);
        $resp = json_decode($resp);
        $this->checkForError($resp);
        return $accountId;
    }

    public function registerCertificate($csr, callable $uploadFile, $timeout = 120, $attempsInterval = 4)
    {
        $deadline = time() + $timeout;

        // --- Проверка предоставленного CSR

        if (is_string($csr)) {
            $csr = trim($csr);
            if ($csr === '') {
                throw new InvalidArgumentException('$csr argument should not to be empty');
            }
        }
        elseif (is_resource($csr)) {
            $resourceType = get_resource_type($csr);
            if ($resourceType !== 'OpenSSL X.509 CSR') {
                throw new InvalidArgumentException('Invalid resource type of $csr argument: ' . $resourceType);
            }
            if (!openssl_csr_export($csr, $csrString, true)) {
                $msg = openssl_error_string();
                throw new Exception('OpenSSL failed to covert CSR into string: ' . $msg);
            }
            $csr = $csrString;
        }
        else {
            throw new InvalidArgumentException('Invalid type of $csr argument: ' . gettype($csr));
        }

        $csrFields = @openssl_csr_get_subject($csr, false);
        if (!is_array($csrFields)) {
            $msg = openssl_error_string();
            throw new Exception('OpenSSL failed to decode CSR: ' . $msg);
        }
        if (!isset($csrFields['commonName'])) {
            throw new Exception("Unexpected error: no 'commonName' parameter found after decoding CSR");
        }
        $domainName = $csrFields['commonName'];

        // --- Отправка запроса на подтверждение владения доменом

        $arguments = [
            'resource' => 'new-authz',
            'identifier' => [
                'type' => 'dns',
                'value' => $domainName
            ]
        ];
        $url = $this->url('/acme/new-authz');
        $resp = $this->signedPost($url, $arguments);
        $resp = json_decode($resp);
        $this->checkForError($resp);

        if (!isset($resp->challenges) || !is_array($resp->challenges)) {
            throw new UnexpectedResponse($this->lastResponse, 'ACME server did not present any challenge');
        }
        foreach ($resp->challenges as $challenge) {
            if (isset($challenge->type) && $challenge->type === 'http-01') {
                if (isset($challenge->token)) $challengeToken = $challenge->token;
                else throw new UnexpectedResponse($this->lastResponse, "ACME server did not present token for 'http-01' challenge type");
                $challengeURL = isset($challenge->uri) ? $challenge->uri : ('/acme/challenge/' . $challengeToken);
                break;
            }
        }
        if (empty($challengeToken)) {
            throw new UnexpectedResponse($this->lastResponse, 'ACME server did not present http-01 challenge token. This class does not support any challenge type besides http-01.');
        }
        if (!preg_match('/^https?\:\/\//i', $challengeURL)) {
            $challengeURL = $this->url($challengeURL);
        }

        $this->checkDeadline($deadline);

        // --- Сохранение файла верификации на сайт

        $header = array(
            'e' => $this->b64_urlencode($this->accountKeyInfo['rsa']['e']),
            'kty' => 'RSA',
            'n' => $this->b64_urlencode($this->accountKeyInfo['rsa']['n'])
        );
        $hash = hash('sha256', json_encode($header), true);
        $challengeFileContent = $challengeToken . '.';
        $challengeFileContent .= $this->b64_urlencode($hash);
        $uploadFile('/.well-known/acme-challenge', $challengeToken, $challengeFileContent);

        // --- Просим ACME-сервер проверить урл

        $arguments = [
            'resource' => 'challenge',
            'type' => 'http-01',
            'keyAuthorization' => $challengeFileContent,
            'token' => $challengeToken
        ];
        $this->checkDeadline($deadline);
        $resp = $this->signedPost($challengeURL, $arguments);
        $resp = json_decode($resp);
        $this->checkForError($resp);

        while (true) {
            $this->checkDeadline($deadline);
            $resp = $this->unsignedGet($challengeURL);
            $resp = json_decode($resp);
            $this->checkForError($resp);
            if (!isset($resp->status)) {
                throw new UnexpectedResponse($this->lastResponse, 'Unexpected response format received from ACME server');
            }
            if ($resp->status === 'pending') {
                sleep($attempsInterval);
            } else {
                break;
            }
        }

        if ($resp->status !== 'valid') {
            throw new Exception('ACME server was not authorized this domain');
        }

        // --- Отправка запроса на регистрацию сертификата

        $csrText = self::pemData($csr);
        $csrText = base64_decode($csrText);
        $csrText = $this->b64_urlencode($csrText);
        $arguments = [
            'resource' => 'new-cert',
            'csr' => $csrText
        ];
        $url = $this->url('/acme/new-cert');
        $resp = $this->signedPost($url, $arguments);
        $curlResponse = $this->getLastResponse();
        $contentType = $curlResponse->getHeader('content-type');
        if ($contentType !== 'application/pkix-cert') {
            throw new UnexpectedResponse($this->lastResponse, 'Unexpected content-type header received from ACME server while certificate registration');
        }

        $result = [
            'domainCertificate' => self::formatPEM($resp, self::PEM_CERTIFICATE)
        ];

        $issuerLink = self::extractLink($curlResponse->getHeader('link'));
        if ($issuerLink) try {
            $req = new CurlRequest;
            $req->ignoreInvalidSSL = $this->ignoreInvalidSSL;
            $resp = $req->httpGet($issuerLink);
            $contentType = $resp->getHeader('content-type');
            if ($contentType === 'application/pkix-cert') {
                $result['issuerCertificate'] = self::formatPEM($resp->getBody(), self::PEM_CERTIFICATE);
            }
        } catch (Exception $e) {
        }

        return $result;
    }

    public function revokeCertificate($certificate)
    {
        if (is_resource($certificate)) {
            $resourceType = get_resource_type($certificate);
            if ($resourceType !== 'OpenSSL X.509') {
                throw new InvalidArgumentException('Invalid resource type of $certificate argument: ' . $resourceType);
            }
            if (!openssl_x509_export($certificate, $certificateText, true)) {
                $msg = openssl_error_string();
                throw new Exception('OpenSSL failed to convert certificate into PEM: ' . $msg);
            }
            $certificate = $certificateText;
        }
        elseif (!is_string($certificate)) {
            throw new InvalidArgumentException('Invalid type of $certificate argument: ' . gettype($certificate));
        }
        $certificateRaw = $this->pemData($certificate);
        $certificateRaw = base64_decode($certificateRaw);
        $arguments = [
            'resource' => 'revoke-cert',
            'certificate' => $this->b64_urlencode($certificateRaw)
        ];
        $url = $this->url('/acme/revoke-cert');
        $resp = $this->signedPost($url, $arguments);
        $lastResponse = $this->getLastResponse();
        if ($resp === '') {
            $code = $lastResponse->getResponseCode();
            if ($code === 200 || $code === 100) {
                return;
            } else {
                throw new UnexpectedResponse($this->lastResponse, 'unexpected response code');
            }
        }
        $resp = json_decode($resp);
        $this->checkForError($resp);
        throw new UnexpectedResponse($this->lastResponse, 'ACME server did not revoke certificate for unknown reason');
    }

    protected function checkDeadline($deadline)
    {
        if (time() > $deadline) {
            throw new Exception('Deadline time reached');
        }
    }

    public static function readPrivateKey($privateKeyText, $password = null)
    {
        $result = @openssl_pkey_get_private($privateKeyText, $password);
        if (!is_resource($result)) {
            $msg = 'OpenSSL failed to decode private key: ' . openssl_error_string();
            throw new Exception($msg);
        }
        return $result;
    }

    protected function checkForError($response)
    {
        if (!is_object($response)) {
            throw new InvalidArgumentException('Invalid type of $response argument: ' . gettype($response));
        }
        if (!isset($response->type, $response->detail)) {
            return;
        }
        if (!preg_match('/^urn\:acme\:error\:/', $response->type)) {
            return;
        }
        throw new AcmeError($response);
    }

    public function url($uri)
    {
        return 'https://' . $this->apiHost . '/' . ltrim($uri, '/');
    }

    public function signedPost($url, array $arguments = [])
    {
        $postdata = json_encode($this->formatRequest($arguments));
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        $curlOptions = [
            CURLOPT_HTTPHEADER => $headers
        ];
        $req = new CurlRequest;
        $req->ignoreInvalidSSL = $this->ignoreInvalidSSL;
        $this->lastResponse = $req->httpPost($url, $postdata, $curlOptions);
        $headers = $this->lastResponse->getHeaders();
        if (isset($headers['replay-nonce'])) {
            $this->lastNonce = $headers['replay-nonce'];
        }
        $result = $this->lastResponse->getBody();
        return $result;
    }

    public function unsignedGet($url)
    {
        $req = new CurlRequest;
        $req->ignoreInvalidSSL = $this->ignoreInvalidSSL;
        $this->lastResponse = $req->httpGet($url);
        $headers = $this->lastResponse->getHeaders();
        if (isset($headers['replay-nonce'])) {
            $this->lastNonce = $headers['replay-nonce'];
        }
        $result = $this->lastResponse->getBody();
        return $result;
    }

    public function getLastNonce()
    {
        if (!is_null($this->lastNonce)) {
            return $this->lastNonce;
        }
        $url = 'https://' . $this->apiHost . '/directory';
        $req = new CurlRequest;
        $req->ignoreInvalidSSL = $this->ignoreInvalidSSL;
        $resp = $req->httpGet($url);
        $headers = $resp->getHeaders();
        if (isset($headers['replay-nonce'])) {
            return $headers['replay-nonce'];
        }
        throw new Exception("No Replay-Nonce header received by request to $url");
    }

    public function formatRequest(array $payload)
    {
        $header = [
            'alg' => 'RS256',
            'jwk' => [
                'kty' => 'RSA',
                'n' => $this->b64_urlencode($this->accountKeyInfo['rsa']['n']),
                'e' => $this->b64_urlencode($this->accountKeyInfo['rsa']['e']),
            ]
        ];
        $protected = $header;
        $protected['nonce'] = $this->getLastNonce();
        $payload64 = $this->b64_urlencode(str_replace('\\/', '/', json_encode($payload)));
        $protected64 = $this->b64_urlencode(json_encode($protected));
        openssl_sign($protected64 . '.' . $payload64, $signed, $this->accountPrivateKey, 'SHA256');
        $signed64 = $this->b64_urlencode($signed);
        $result = [
            'header' => $header,
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $signed64
        ];
        return $result;
    }

    const PEM_CERTIFICATE = 'CERTIFICATE';
    const PEM_PRIVATE_KEY = 'PRIVATE KEY';
    const PEM_CERTIFICATE_REQUEST = 'CERTIFICATE REQUEST';

    public static function formatPEM($binaryData, $pemFileType)
    {
        static $allow = [
            self::PEM_CERTIFICATE,
            self::PEM_PRIVATE_KEY,
            self:: PEM_CERTIFICATE_REQUEST
        ];
        if (!in_array($pemFileType, $allow)) {
            throw new Exception('Unsupported PEM file type: ' . $pemFileType);
        }
        $encodedData = base64_encode($binaryData);
        $encodedData = chunk_split($encodedData, 64, "\n");
        $result = "-----BEGIN {$pemFileType}-----\n";
        $result .= trim($encodedData) . "\n";
        $result .= "-----END {$pemFileType}-----";
        return $result;
    }

    public static function b64_urlencode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public static function b64_urldecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function getApiHost()
    {
        return $this->apiHost;
    }

    public function getAccountPrivateKey()
    {
        return $this->accountPrivateKey;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }



    protected static function pemData($pemText, $returnType = 'multiLine')
    {
        $pemLinez = explode("\n", $pemText);
        $pemLines = [];
        foreach ($pemLinez as $line) {
            $line = trim($line);
            if ($line !== '') {
                $pemLines[] = $line;
            }
        }
        $linesCount = count($pemLines);
        if ($linesCount < 3) {
            throw new InvalidArgumentException('Invalid PEM file format (1)');
        }
        if (preg_match('/^\-.+\-$/', $pemLines[0])) {
            unset($pemLines[0]);
        }
        $lastIndex = $linesCount - 1;
        if (preg_match('/^\-.+\-$/', $pemLines[$lastIndex])) {
            unset($pemLines[$lastIndex]);
        }
        $result = [];
        foreach ($pemLines as $line) {
            if (is_int(strpos($line, '-'))) {
                throw new InvalidArgumentException('Invalid PEM file format (2)');
            } else {
                $result[] = $line;
            }
        }

        if ($returnType === 'multiLine') {
            return implode("\n", $result);
        }

        if ($returnType === 'singleLine') {
            return implode('', $result);
        }

        if ($returnType === 'array') {
            return $result;
        }

        throw new InvalidArgumentException('Invalid type of result: ' . $returnType);
    }

    protected function extractLink($headerValue) {
        if (!is_string($headerValue)) return null;
        if (!preg_match('/\<\s*(https?\:\/\/.+)\s*\>/iU', $headerValue, $link)) return null;
        return trim($link[1]);
    }
}
