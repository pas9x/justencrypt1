<?php

namespace app;

use \Exception;
use \phpseclib\File\X509;

class CA
{
    protected $certificates = [];

    public function __construct()
    {
        $tryFiles = [
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            DATADIR . '/cacert.pem'
        ];
        $locations = function_exists('openssl_get_cert_locations') ? openssl_get_cert_locations() : [];
        if (!empty($locations['default_cert_file'])) $tryFiles[] = $locations['default_cert_file'];
        if (!empty($locations['default_cert_file_env'])) $tryFiles[] = getenv($locations['default_cert_file_env']);
        if (!empty($locations['ini_cafile'])) $tryFiles[] = $locations['ini_cafile'];
        $tryFiles = array_unique($tryFiles);
        foreach ($tryFiles as $file)
            if ($file && file_exists($file)) try {
                $this->certificates = self::readFile($file);
                return;
            } catch (Exception $e) {
            }
        $tryDirs = [
            ini_get('openssl.capath')
        ];
        if (!empty($locations['default_cert_dir'])) $tryDirs[] = $locations['default_cert_dir'];
        if (!empty($locations['default_cert_dir_env'])) $tryDirs[] = getenv($locations['default_cert_dir_env']);
        if (!empty($locations['ini_capath'])) $tryDirs[] = $locations['ini_capath'];
        $tryDirs = array_unique($tryDirs);
        foreach ($tryDirs as $dir)
            if ($dir && is_dir($dir)) try {
                $this->certificates = self::readDir($dir);
                return;
            } catch (Exception $e) {
            }
        throw new Exception('CA certificates storage not found');
    }

    public function addCaCert($cert)
    {
        if (is_string($cert)) {
            $certText = $cert;
            $cert = @openssl_x509_parse($certText);
            if (!is_array($cert)) {
                throw new Exception('Failed to parse $cert as x509 certificate');
            }
            $cert['text'] = $certText;
        } elseif (is_array($cert)) {
            if (!isset($cert['text'])) {
                throw new Exception("Certificate info should contain 'text' element (certificate himself in PEM format)");
            }
        } else {
            throw new Exception('Invalid type of $cert argument: ' . gettype($cert));
        }
        $certKey = self::trimKeyid($cert['extensions']['subjectKeyIdentifier']);
        if (!isset($this->certificates[$certKey])) {
            $this->certificates[$certKey] = [];
        }
        $this->certificates[$certKey][] = $cert;
    }

    protected static function readFile($file)
    {
        if (!file_exists($file)) {
            throw new Exception("File $file does not exist");
        }
        $caText = file_get_contents($file);
        if (!preg_match_all('/\\-{2,100}\s{0,10}BEGIN CERTIFICATE\s{0,10}\\-{2,100}\s{1,10}(.+)\s{1,10}\\-{2,100}\s{0,10}END CERTIFICATE\s{0,10}\\-{2,100}([^-]|$)/siU', $caText, $matches)) {
            throw new Exception("CA certificates not found in file $file (1)");
        }
        $certificates = [];
        foreach ($matches[0] as $match) {
            $certificates[] = trim($match);
        }

        $result = [];
        foreach ($certificates as $certificate) {
            $certInfo = @openssl_x509_parse($certificate, false);
            if (!isset($certInfo['extensions']['subjectKeyIdentifier'])) continue;
            $certKey = self::trimKeyid($certInfo['extensions']['subjectKeyIdentifier']);
            $certInfo['text'] = $certificate;
            if (!isset($result[$certKey])) $result[$certKey] = [];
            $result[$certKey][] = $certInfo;
        }

        if (count($result) < 2) {
            throw new Exception("CA certificates not found in file $file (2)");
        }

        return $result;
    }

    protected function readDir($dir)
    {
        if (!is_dir($dir)) {
            throw new Exception("Directory $dir not found");
        }
        $fsNodes = scandir($dir);
        $result = [];
        foreach ($fsNodes as $fsNode) {
            $certPath = $dir . '/' . $fsNode;
            if (!is_file($certPath)) continue;
            $fileContent = file_get_contents($certPath);
            $certInfo = @openssl_x509_parse($fileContent);
            if (!isset($certInfo['extensions']['subjectKeyIdentifier'])) continue;
            $certKey = self::trimKeyid($certInfo['extensions']['subjectKeyIdentifier']);
            $certInfo['text'] = $fileContent;
            if (!isset($result[$certKey])) $result[$certKey] = [];
            $result[$certKey][] = $certInfo;
        }

        if (count($result) < 2) {
            throw new Exception("CA certificates not found in directory $dir");
        }

        return $result;
    }

    public function getChain($cert, $reverse = false)
    {
        $chain = $this->getChainReverse($cert);
        return $reverse ? $chain : array_reverse($chain);
    }

    protected static function trimKeyid($keyId)
    {
        return trim(preg_replace('/^keyid\:/i', '', $keyId));
    }

    protected function getChainReverse($cert)
    {
        if (is_array($cert)) {
            if (isset($cert['text'])) {
                $certInfo = $cert;
                $certText = $cert['text'];
            } else {
                throw new Exception("\$cert array should contain 'text' element");
            }
        } elseif (is_string($cert)) {
            $certInfo = openssl_x509_parse($cert, false);
            $certText = $cert;
            if (!is_array($certInfo)) {
                throw new Exception('Failed to parse $cert as x509 certificate');
            }
        } else {
            throw new Exception('Invalid type of $cert argument: ' . gettype($cert));
        }

        $result = [$certText];
        if (isset($certInfo['extensions']['authorityKeyIdentifier'])) {
            $issuerKey = self::trimKeyid($certInfo['extensions']['authorityKeyIdentifier']);
            if (isset($this->certificates[$issuerKey])) {
                foreach ($this->certificates[$issuerKey] as $supposedIssuerCert)
                    if (self::checkIssuer($supposedIssuerCert['text'], $certText)) {
                        $issuerCert = $supposedIssuerCert['text'];
                        break;
                    }
            }
            if (empty($issuerCert)) {
                $message = 'This certificate chain has one certificate with unknown issuer.';
                $additional = [];
                if (isset($certInfo['subject']['commonName'])) {
                    $additional[] = 'commonName=' . $certInfo['subject']['commonName'];
                }
                if (isset($certInfo['extensions']['subjectKeyIdentifier'])) {
                    $additional[] = 'subjectKeyIdentifier=' . self::trimKeyid($certInfo['extensions']['subjectKeyIdentifier']);
                }
                if (isset($certInfo['extensions']['authorityKeyIdentifier'])) {
                    $additional[] = 'authorityKeyIdentifier=' . self::trimKeyid($certInfo['extensions']['authorityKeyIdentifier']);
                }
                $message .= 'Bad unit: ' . implode(', ', $additional);
                throw new Exception($message);
            }
            $issuerChain = self::getChainReverse($issuerCert);
            $result = array_merge($result, $issuerChain);
        }
        return $result;
    }

    public static function checkIssuer($certIssuer, $certChild)
    {
        $validator = new X509;
        $validator->loadCA($certIssuer);
        $validator->loadX509($certChild);
        $result = @$validator->validateSignature();
        return ($result === true);
    }

    public function getCertificates()
    {
        return $this->certificates;
    }
}