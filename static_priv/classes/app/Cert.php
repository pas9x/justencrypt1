<?php

namespace app;

use \ErrorMessage;
use \Exception;
use \phpseclib\Net\SCP;
use \pas9x\letsencrypt\LetsEncrypt;
use phpseclib\Net\SFTP;

class Cert {
    public static function listCert($pg = null, $pp = 25)
    {
        $query = 'SELECT * FROM cert ORDER BY idSsh';
        if (is_int($pg)) {
            $pp = intval($pp);
            if ($pp < 1) throw new Exception('Invalid value of $pp argument');
            $from = ($pg - 1) * $pp;
            $query .= " LIMIT $from, $pp";
        }
        $stmt = dbQuery($query);
        $result = [];
        while ($row = $stmt->fetchAssoc(TABLE_MASK_CERT)) {
            $row['issuedTimestamp'] = strtotime($row['issued']);
            $row['expireTimestamp'] = strtotime($row['expire']);
            $result[] = $row;
        }
        return $result;
    }

    public static function certExists($idCert)
    {
        $db = getDB();
        return $db->hasResult('SELECT idCert FROM cert WHERE idCert=?', intval($idCert));
    }

    public static function getCert($idCert)
    {
        $idCert = intval($idCert);
        $stmt = dbQuery('SELECT * FROM cert WHERE idCert=?', $idCert);
        $result = $stmt->fetchAssoc(TABLE_MASK_CERT);
        if (empty($result)) {
            throw new ErrorMessage("Сертификат #$idCert не нафжен");
        }
        $result['issuedTimestamp'] = strtotime($result['issued']);
        $result['expireTimestamp'] = strtotime($result['expire']);
        return $result;
    }

    protected static function getLE()
    {
        $leAccountKey = getOption('leAccountKey', '');
        if ($leAccountKey === '') {
            throw new ErrorMessage('Отсутствует приватный ключ аккаунта Let`s Encrypt. Перейдите в настройки системы и зарегистрируйте новый аккаунт.');
        }
        $result = new LetsEncrypt($leAccountKey);
        return $result;
    }

    public static function deleteCert($idCert)
    {
        $idCert = intval($idCert);
        $cert = self::getCert($idCert);
        try {
            self::revokeCert($idCert);
        } catch (Exception $e) {
            errorLog('Failed to revoke certificate'. $e);
        }
        $ssh = SSH::getAccount($cert['idSsh']);
        if (!$ssh['shared']) {
            SSH::deleteAccount($cert['idSsh']);
        }
        dbQuery('DELETE FROM cert WHERE idCert=?', $idCert);
        dbQuery('DELETE FROM exports WHERE idCert=?', $idCert);
    }

    public static function revokeCert($idCert)
    {
        $idCert = intval($idCert);
        $cert = self::getCert($idCert);
        $le = self::getLE();
        try {
            $le->revokeCertificate($cert['certDomain']);
        } catch (Exception $e) {
            throw new ErrorMessage($e->getMessage());
        }
    }

    public static function testVerifyDynamic($idSsh, $domain, $documentRoot)
    {
        $idSsh = intval($idSsh);
        try {
            $ssh = SSH::connect($idSsh);
        } catch (Exception $e) {
            throw new ErrorMessage('Не удалось подключиться к SSH-серверу: ' . $e->getMessage());
        }
        $scp = new SCP($ssh);

        $documentRoot = strval($documentRoot);
        if ($documentRoot === '') {
            throw new ErrorMessage('Вы не указали корневую директорию сайта');
        }

        $testFileUri = '/test/check_' . rand(1000, 999999) . '.txt';
        $testFileUrl = 'http://' . $domain . $testFileUri;
        $testFileDir = $documentRoot . '/test';
        $testFilePath = $documentRoot . $testFileUri;
        $testFileContent = strtoupper(keygen(16));

        $rollback = function() use($ssh, $scp, $testFileDir) {
            try {$ssh->delete($testFileDir, true);}
            catch (Exception $e) {}
            try {$ssh->disconnect();}
            catch (Exception $e) {}
        };

        try {
            if (!$ssh->is_dir($testFileDir)) {
                $ok = $ssh->mkdir($testFileDir, 0711, true);
                if (!$ok) throw new ErrorMessage("На удалённом сервере не удалось создать директорию $testFileDir");
            }
            $ok = $scp->put($testFilePath, $testFileContent, SCP::SOURCE_STRING);
            if (!$ok) {
                throw new ErrorMessage("На удалённый сервер не удалось загрузить файл $testFilePath");
            }
            try {$ssh->chmod(0644, $testFilePath);}
            catch (Exception $e) {}
            $receivedContent = httpGet($testFileUrl);
            if ($receivedContent !== $testFileContent) {
                throw new ErrorMessage("Содержимое файла $testFilePath не совпадает с контентом полученным по ссылке $testFileUrl");
            }
        } catch (ErrorMessage $e) {
            $error = $e;
        } catch (Exception $e) {
            $error = new ErrorMessage($e->getMessage());
        }
        $rollback();
        if (isset($error)) {
            throw $error;
        }
    }

    public static function testVerify($idCert)
    {
        $idCert = intval($idCert);
        $cert = self::getCert($idCert);
        self::testVerifyDynamic($cert['idSsh'], $cert['domain'], $cert['documentRoot']);
    }

    protected static function registerCertOnly($domain, $documentRoot, $idSsh, $ignoreExisting = false)
    {
        $errors = [];
        $db = getDB();

        $domain = strtolower($domain);
        if ($domain === '') {
            $errors[] = 'Вы не указали домен на который регистрируется сертификат';
        } elseif (!preg_match('/^[a-z0-9\\.\\-]{3,255}$/', $domain)) {
            $errors[] = 'Имя домена указано неверно';
        } elseif (!$ignoreExisting && $db->hasResult('SELECT idCert FROM cert WHERE domain=?', $domain)) {
            $errors[] = "У вас уже есть сертификат на домен $domain";
        }

        $documentRoot = strval($documentRoot);
        if ($documentRoot === '') {
            $errors[] = 'Не указана корневая директория домена';
        }

        $idSsh = intval($idSsh);
        try {
            self::testVerifyDynamic($idSsh, $domain, $documentRoot);
        } catch (ErrorMessage $e) {
            $errors += $e->getMessages();
        }

        try {
            $le = self::getLE();
        } catch (ErrorMessage $e) {
            $errors += $e->getMessages();
        }

        if ($errors) {
            throw new ErrorMessage($errors);
        }

        try {
            $ssh = SSH::connect($idSsh);
        } catch (Exception $e) {
            throw new ErrorMessage("Не удалось подключиться к SSH-серверу: " . $e->getMessage());
        }
        $scp = new SCP($ssh);

        $uploadFileRollback = function() {};
        $uploadFile = function($challengeFileDir, $challengeFileName, $challengeFileContent)
        use($ssh, $scp, $documentRoot, &$uploadFileRollback) {
            if ($challengeFileDir !== '/.well-known/acme-challenge') {
                throw new Exception('Unexpected confirmation file directory: ' . $challengeFileDir);
            }
            $fullDir = $documentRoot . '/' . $challengeFileDir;
            $fullPath = $fullDir . '/' . $challengeFileName;
            $uploadFileRollback = function() use($ssh, $documentRoot) {
                try {$ssh->delete($documentRoot . '/.well-known', true);}
                catch (Exception $e) {}
            };
            if (!$ssh->is_dir($fullDir)) {
                $ok = $ssh->mkdir($fullDir, 0711, true);
                if (!$ok) throw new ErrorMessage("На удалённом сервере не удалось создать директорию $fullDir");
            }
            $ok = $scp->put($fullPath, $challengeFileContent, SCP::SOURCE_STRING);
            if (!$ok) {
                throw new ErrorMessage("Не удалось загрузить файл $fullPath на удалённый сервер");
            }
            try {$ssh->chmod(0644, $fullPath);}
            catch (Exception $e) {}
        };

        try {
            $certKey = LetsEncrypt::generatePrivateKey(true);
            $csrTemplate = getOption('defaultCsrTemplate');
            $csr = LetsEncrypt::generateCSR(
                $certKey,
                $domain,
                getOption('adminEmail'),
                $csrTemplate['countryName'],
                $csrTemplate['stateOrProvinceName'],
                $csrTemplate['localityName'],
                $csrTemplate['organizationName'],
                $csrTemplate['organizationalUnitName']
            );
            $certData = $le->registerCertificate($csr, $uploadFile);
        } catch (Exception $e) {
            $uploadFileRollback();
            debugLog('Certificate registration failed', $e);
            throw $e;
        }

        $uploadFileRollback();
        return [
            $certKey,
            $csr,
            $certData
        ];
    }

    public static function addCert($certDomain, $certIssuer, $privateKey, $csr, $idSsh, $documentRoot, $addTime = null)
    {
        $idSsh = intval($idSsh);
        $ssh = SSH::getAccount($idSsh);

        $certParsed = openssl_x509_parse($certDomain, false);
        if (!is_array($certParsed)) {
            throw new Exception('Failed to parse provided string as PEM certificate');
        }

        if (!isset($certParsed['subject']['commonName'])) {
            throw new Exception("This certificate does not contain 'commonName' element");
        }
        $domain = $certParsed['subject']['commonName'];

        if (!isset($certParsed['validFrom_time_t'])) {
            throw new Exception('This certificate has no validFrom_time_t field');
        }
        $issued = $certParsed['validFrom_time_t'];

        if (!isset($certParsed['validTo_time_t'])) {
            throw new Exception('This certificate has no validTo_time_t field');
        }
        $expire = $certParsed['validTo_time_t'];

        $db = getDB();

        if ($db->hasResult('SELECT idCert FROM cert WHERE domain=?', $domain)) {
            throw new ErrorMessage("Сертификат для домена $domain уже существует");
        }

        $db->insert(
            'cert',
            null,
            $idSsh,
            $domain,
            $documentRoot,
            is_int($addTime) ? $addTime : time(),
            date('Y-m-d', $issued),
            date('Y-m-d', $expire),
            $privateKey,
            $certDomain,
            $certIssuer,
            $csr,
            crc32($certDomain)
        );
        $result = $db->insertId();

        return $result;
    }

    public static function registerCert($domain, $documentRoot, $idSsh)
    {
        $idSsh = intval($idSsh);
        list($certKey, $csr, $certData) = self::registerCertOnly($domain, $documentRoot, $idSsh, false);
        $now = time();
        $certExpire = self::getCertificateExpire($certData['domainCertificate']);
        $db = getDB();
        $db->insert(
            'cert',
            null,
            $idSsh,
            $domain,
            $documentRoot,
            $now,
            date('Y-m-d', $now),
            date('Y-m-d', $certExpire),
            $certKey,
            $certData['domainCertificate'],
            (isset($certData['issuerCertificate']) ? $certData['issuerCertificate'] : ''),
            $csr,
            crc32($certData['domainCertificate'])
        );
        $idCert = $db->insertId();
        return $idCert;
    }

    public static function reissueCert($idCert)
    {
        $idCert = intval($idCert);
        $cert = self::getCert($idCert);
        list($certKey, $csr, $certData) = self::registerCertOnly(
            $cert['domain'],
            $cert['documentRoot'],
            $cert['idSsh'],
            true
        );
        dbQuery(
            'UPDATE cert SET issued=?, expire=?, privateKey=?, certDomain=?, certIssuer=?, csr=?, certDomainHash=? WHERE idCert=?',
            date('Y-m-d'),
            date('Y-m-d', self::getCertificateExpire($certData['domainCertificate'])),
            $certKey,
            $certData['domainCertificate'],
            (empty($certData['issuerCertificate']) ? '' : $certData['issuerCertificate']),
            $csr,
            crc32($certData['domainCertificate']),
            $idCert
        );
    }

    public static function saveCert($idCert, $idSsh, $documentRoot)
    {
        $idCert = intval($idCert);
        $idSsh = intval($idSsh);
        $cert = Cert::getCert($idCert);
        self::testVerifyDynamic($idSsh, $cert['domain'], $documentRoot);
        dbQuery('UPDATE cert SET documentRoot=?, idSsh=? WHERE idCert=?', $documentRoot, $idSsh, $idCert);
    }

    public static function getCertificateExpire($cert)
    {
        if (!is_string($cert)) {
            throw new Exception('Invalid type of $cert argument: ' . gettype($cert));
        }
        $cert = @openssl_x509_parse($cert);
        if (!is_array($cert)) {
            throw new Exception('Failed to parse $cert as x509 certificate');
        }
        if (!isset($cert['validTo_time_t'])) {
            throw new Exception('This certificate has no validTo_time_t field');
        }
        return $cert['validTo_time_t'];
    }

    public static function getCSRdomain($csr)
    {
        $csrParsed = openssl_csr_get_subject($csr, false);
        if (!is_array($csrParsed)) {
            throw new Exception('Failed to parse provided string as CSR');
        }
        if (!isset($csrParsed['commonName'])) {
            throw new Exception("This CSR does not contain 'commonName' element");
        }
        if (empty($csrParsed['commonName'])) {
            throw new Exception("This CSR has empty 'commonName' element");
        }
        return $csrParsed['commonName'];
    }

    public static function getCertificateDomain($cert)
    {
        $certParsed = openssl_x509_parse($cert, false);
        if (!is_array($certParsed)) {
            throw new Exception('Failed to parse provided string as PEM certificate');
        }
        if (!isset($certParsed['subject']['commonName'])) {
            throw new Exception("This certificate does not contain 'commonName' element");
        }
        if (empty($certParsed['subject']['commonName'])) {
            throw new Exception("This certificate has empty 'commonName' element");
        }
        return $certParsed['subject']['commonName'];
    }
}