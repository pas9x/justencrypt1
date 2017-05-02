<?php

namespace app;

use \Exception;
use \ErrorMessage;
use \UnexpectedError;
use \phpseclib\Net\SSH2;
use \phpseclib\Net\SFTP;
use \phpseclib\Crypt\RSA;

class SSH {
    const ACCESS_TYPE_PASSWORD = 1;
    const ACCESS_TYPE_KEY = 2;

    public static function listAccounts($pg = null, $pp = 25, $shared = true) {
        if ($shared === true) {
            $where = 'WHERE shared=1';
        } elseif ($shared === false) {
            $where = 'WHERE shared=0';
        } elseif ($shared === null) {
            $where = '';
        } else {
            throw new Exception('Invalid value of $shared argument');
        }
        if (is_int($pg)) {
            $pp = intval($pp);
            if ($pp < 1) throw new Exception('Invalid value of $pp argument');
            $from = ($pg - 1) * $pp;
            $stmt = dbQuery("SELECT * FROM ssh $where ORDER BY idSsh LIMIT $from, $pp");
        } else {
            $stmt = dbQuery("SELECT * FROM ssh $where ORDER BY idSsh");
        }
        $result = [];
        while ($account = $stmt->fetchAssoc(TABLE_MASK_SSH)) {
            $account['authValue'] = decrypt($account['authValue']);
            $result[] = $account;
        }
        return $result;
    }

    public static function addAccount(
        $isShared,
        $sharedName,
        $host,
        $port,
        $login,
        $authType,
        $authValue
    ) {
        $errors = [];
        $db = getDB();

        $isShared = (boolean)$isShared;

        if ($isShared) {
            $sharedName = strval($sharedName);
            if ($sharedName === '') {
                $errors[] = 'Не указано название ssh-аккаунта';
            } elseif (!checkLength($sharedName, 1, 50)) {
                $errors[] = 'Название ssh-аккаунта должно иметь длину от 1 до 50 символов';
            } elseif ($db->hasResult('SELECT idSsh FROM ssh WHERE sharedName=?', $sharedName)) {
                $errors[] = "SSH-аккаунт с названием '$sharedName' уже существует";
            }
        } else {
            $sharedName = '';
        }

        $host = strval($host);
        if ($host === '') {
            $errors[] = 'Не указан адрес ssh-сервера';
        } elseif (!checkLength($host, 1, 50)) {
            $errors[] = 'Адрес ssh-сервера должен иметь длину от 1 до 50 символов';
        }

        $port = intval($port);
        if ($port < 1 || $port > 65535) {
            $errors[] = 'Порт ssh-сервера должен быть числом в диапазоне от 1 до 65535';
        }

        $login = strval($login);
        if ($login === '') {
            $errors[] = 'Не указан логин для авторизации на ssh-сервере';
        } elseif (!checkLength($login, 1, 50)) {
            $errors[] = 'Логин должен иметь длину от 1 до 50 символов';
        }

        $authType = intval($authType);
        if ($authType !== self::ACCESS_TYPE_PASSWORD && $authType !== self::ACCESS_TYPE_KEY) {
            $errors[] = 'Недопустимое значение аргумента $accessType';
        }

        $authValue = strval($authValue);
        if ($authValue === '') {
            $errors[] = 'Не указан пароль/ключ для авторизации на ssh-сервере';
        }

        if ($errors) {
            throw new ErrorMessage($errors);
        }

        try {
            $ssh = new SSH2($host, $port, 10);
            if ($authType === self::ACCESS_TYPE_PASSWORD) {
                $auth = $authValue;
            } else {
                $auth = new RSA;
                $auth->loadKey($authValue);
            }
            if (!$ssh->login($login, $auth)) {
                throw new Exception('SSH-authentication failed');
            }
            $ssh->disconnect();
        } catch (UnexpectedError $e) {
            $msg = 'Не удалось подключиться к указанному вами ssh-серверу (1): ' . $e->errstr;
            throw new ErrorMessage($msg);
        } catch (Exception $e) {
            $msg = 'Не удалось подключиться к указанному вами ssh-серверу (2):' . $e->getMessage();
            throw new ErrorMessage($msg);
        }

        $db->insert(
            'ssh',
            null,
            $isShared,
            $sharedName,
            $host,
            $port,
            $login,
            $authType,
            encrypt($authValue)
        );
        $idSsh = $db->insertId();

        return $idSsh;
    }

    public static function saveAccount(
        $idSsh,
        $isShared,
        $sharedName,
        $host,
        $port,
        $login,
        $authType,
        $authValue
    ) {
        $errors = [];
        $db = getDB();

        $idSsh = intval($idSsh);
        if (!self::accountExists($idSsh)) {
            throw new ErrorMessage("SSH-аккаунт #$idSsh не существует");
        }

        $isShared = (boolean)$isShared;

        if ($isShared) {
            $sharedName = strval($sharedName);
            if ($sharedName === '') {
                $errors[] = 'Не указано название ssh-аккаунта';
            } elseif (!checkLength($sharedName, 1, 50)) {
                $errors[] = 'Название ssh-аккаунта должно иметь длину от 1 до 50 символов';
            } elseif ($db->hasResult('SELECT idSsh FROM ssh WHERE sharedName=? AND idSsh<>?', $sharedName, $idSsh)) {
                $errors[] = "Уже существует другой ssh-аккаунт с названием '$sharedName'";
            }
        } else {
            $sharedName = '';
        }

        $host = strval($host);
        if ($host === '') {
            $errors[] = 'Не указан адрес ssh-сервера';
        } elseif (!checkLength($host, 1, 50)) {
            $errors[] = 'Адрес ssh-сервера должен иметь длину от 1 до 50 символов';
        }

        $port = intval($port);
        if ($port < 1 || $port > 65535) {
            $errors[] = 'Порт ssh-сервера должен быть числом в диапазоне от 1 до 65535';
        }

        $login = strval($login);
        if ($login === '') {
            $errors[] = 'Не указан логин для авторизации на ssh-сервере';
        } elseif (!checkLength($login, 1, 50)) {
            $errors[] = 'Логин должен иметь длину от 1 до 50 символов';
        }

        $authType = intval($authType);
        if ($authType !== self::ACCESS_TYPE_PASSWORD && $authType !== self::ACCESS_TYPE_KEY) {
            $errors[] = 'Недопустимое значение аргумента $accessType';
        }

        $authValue = strval($authValue);
        if ($authValue === '') {
            $errors[] = 'Не указан пароль/ключ для авторизации на ssh-сервере';
        }

        if ($errors) {
            throw new ErrorMessage($errors);
        }

        try {
            $ssh = new SSH2($host, $port, 10);
            if ($authType === self::ACCESS_TYPE_PASSWORD) {
                $auth = $authValue;
            } else {
                $auth = new RSA;
                $auth->loadKey($authValue);
            }

            if (!$ssh->login($login, $auth)) {
                throw new Exception('SSH-authentication failed');
            }
            $ssh->disconnect();
        } catch (UnexpectedError $e) {
            $msg = 'Не удалось подключиться к указанному вами ssh-серверу (1): ' . $e->errstr;
            throw new ErrorMessage($msg);
        } catch (Exception $e) {
            $msg = 'Не удалось подключиться к указанному вами ssh-серверу (2):' . $e->getMessage();
            throw new ErrorMessage($msg);
        }

        $db->query(
            'UPDATE ssh SET shared=?, sharedName=?, host=?, port=?, login=?, authType=?, authValue=? WHERE idSsh=?',
            $isShared, $sharedName, $host, $port, $login, $authType, encrypt($authValue), $idSsh
        );
    }

    public static function accountExists($idSsh) {
        $idSsh = intval($idSsh);
        $db = getDB();
        return $db->hasResult('SELECT idSsh FROM ssh WHERE idSsh=?', $idSsh);
    }

    public static function getAccount($idSsh) {
        $idSsh = intval($idSsh);
        $stmt = dbQuery('SELECT * FROM ssh WHERE idSsh=?', $idSsh);
        $result = $stmt->fetchAssoc(TABLE_MASK_SSH);
        $result['authValue'] = decrypt($result['authValue']);
        if (empty($result)) {
            throw new ErrorMessage("SSH-аккаунт #$idSsh не найден");
        } else {
            return $result;
        }
    }

    public static function deleteAccount($idSsh)
    {
        $idSsh = intval($idSsh);
        $db = getDB();
        $stmt = $db->query('SELECT idCert FROM cert WHERE idSsh=?', $idSsh);
        $busy = [];
        while ($row = $stmt->fetchAssoc('i')) {
            $busy[] = $row['idCert'];
        }
        $cnt = count($busy);
        if ($cnt > 0) {
            if ($cnt <= 10) $busy = implode(', ', $busy);
            else $busy = implode(', ', array_slice($busy, 0, 10)) . '...';
            throw new ErrorMessage("Удалить аккаунт #$idSsh невозможно, так как он используется сертификатами ($busy)");
        }
        $db->query('DELETE FROM ssh WHERE idSsh=?', $idSsh);
    }

    /**
     * @param int $idSsh
     * @return SFTP
     * @throws ErrorMessage
     * @throws Exception
     */
    public static function connect($idSsh) {
        $sshAccount = self::getAccount($idSsh);
        $ssh = new SFTP($sshAccount['host'], $sshAccount['port'], 10);
        if ($sshAccount['authType'] === SSH::ACCESS_TYPE_PASSWORD) {
            $ok = $ssh->login($sshAccount['login'], $sshAccount['authValue']);
        } elseif ($sshAccount['authType'] === SSH::ACCESS_TYPE_KEY) {
            $key = new RSA;
            $key->loadKey($sshAccount['authValue']);
            $ok = $ssh->login($sshAccount['login'], $key);
        }
        if (!$ok) {
            try {$ssh->disconnect();}
            catch (Exception $e) {}
            throw new Exception('SSH authentication failed');
        }
        return $ssh;
    }
}