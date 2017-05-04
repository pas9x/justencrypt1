<?php

namespace app;

use \Exception;
use \ErrorMessage;
use \ZipArchive;

class Backup
{
    public static function createBackup($zipPassword = '')
    {
        $certificates = Cert::listCert();
        $sshAccounts = SSH::listAccounts();
        $backup = [
            'info' => [
                'releaseVersion' => RELEASE_VERSION,
                'releaseCommit' => RELEASE_COMMIT,
                'timestamp' => time()
            ],
            'adminEmail' => getOption('adminEmail', ''),
            'leAccountKey' => getOption('leAccountKey', ''),
            'leAccountID' => getOption('leAccountID', ''),
            'cert' => [],
            'ssh' => [],
        ];

        foreach ($certificates as $cert) {
            $exports = Exports::listExports(null, null, $cert['idCert']);
            $certBackup = [
                'idSsh' => $cert['idSsh'],
                'domain' => $cert['domain'],
                'documentRoot' => $cert['documentRoot'],
                'addTime' => $cert['addTime'],
                'privateKey' => $cert['privateKey'],
                'certDomain' => $cert['certDomain'],
                'certIssuer' => $cert['certIssuer'],
                'csr' => $cert['csr'],
            ];
            if ($exports) {
                $certBackup['exports'] = [];
                foreach ($exports as $export) {
                    $exportBackup = [
                        'exporterName' => $export['exporterName'],
                        'options' => $export['options'],
                        'finalCommand' => $export['finalCommand'],
                    ];
                    $certBackup['exports'][] = $exportBackup;
                }
            }
            $backup['cert'][] = $certBackup;
        }

        foreach ($sshAccounts as $sshAccount) {
            $idSsh = $sshAccount['idSsh'];
            $sshBackup = [
                'shared' => $sshAccount['shared'],
                'sharedName' => $sshAccount['sharedName'],
                'host' => $sshAccount['host'],
                'port' => $sshAccount['port'],
                'login' => $sshAccount['login'],
                'authType' => $sshAccount['authType'],
                'authValue' => $sshAccount['authValue'],
            ];
            $backup['ssh'][$idSsh] = $sshBackup;
        }

        $backupJson = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        unset($backup);

        $fileNameBase = DATADIR . '/backups/backup_' . date('Y_m_d');
        $fileNameTry = $fileNameBase . '.zip';
        if (!file_exists($fileNameTry)) {
            $fileName = $fileNameTry;
        } else {
            for ($j=1; $j<1000; $j++) {
                $fileNameTry = $fileNameBase . '_x' . $j . '.zip';
                if (!file_exists($fileNameTry)) {
                    $fileName = $fileNameTry;
                    break;
                }
            }
        }
        if (empty($fileName)) {
            throw new ErrorMessage('Не удалось сгенерировать имя файла для бэкапа');
        }
        $zip = new ZipArchive;
        $status = $zip->open($fileName, ZipArchive::OVERWRITE);
        if ($status !== true) {
            throw new Exception("Failed to create $fileName as new zip archive. Status code = $status");
        }
        if ($zipPassword !== '') {
            if (method_exists($zip, 'setPassword')) {
                $zip->setPassword($zipPassword);
            } else {
                throw new ErrorMessage('Ваша версия php не поддерживает установку паролей на zip-архивы');
            }
        }
        getDB(true);
        $zip->addFromString('backup.json', $backupJson);
        $zip->addFile(DATADIR . '/db.sqlite', 'db.sqlite');
        $zip->addFile(DATADIR . '/config.php', 'config.php');
        $zip->close();

        return $fileName;
    }

    public static function importBackup($fileName, &$errors, $zipPassword = '')
    {
        $errors = [];

        if (!file_exists($fileName)) {
            throw new Exception("File $fileName does not exist");
        }
        $zip = new ZipArchive;
        $status = $zip->open($fileName);
        if ($status !== true) {
            throw new ErrorMessage("Не удалось открыть предоставленный вами файл как zip-архив. Код ошибки = $status");
        }
        if ($zipPassword !== '') {
            if (method_exists($zip, 'setPassword')) {
                $zip->setPassword($zipPassword);
            } else {
                throw new ErrorMessage('Ваша версия php не поддерживает установку паролей на zip-архивы');
            }
        }
        $backupJson = @$zip->getFromName('backup.json');
        if (!is_string($backupJson)) {
            throw new ErrorMessage('Из предоставленного вами zip-архива не удалось извлечь файл backup.json');
        }
        $zip->close();
        $backup = json_decode($backupJson);
        if (!is_object($backup)) {
            throw new ErrorMessage('Не удалось спарсить файл backup.json из предоставленного вами zip-архива');
        }

        if (empty($backup->cert) || !is_array($backup->cert)) {
            throw new ErrorMessage('В файле backup.json сертификаты не найдены');
        }

        if (empty($backup->ssh) || !is_object($backup->ssh)) {
            throw new ErrorMessage('В файле backup.json не найдены ssh-аккаунты');
        }

        // $adminEmail = getOption('adminEmail', '');
        $leAccountKey = getOption('leAccountKey', '');
        $leAccountID = getOption('leAccountID', 0);

        if (empty($leAccountKey) && empty($leAccountID))
            if (!empty($backup->leAccountKey) && !empty($backup->leAccountID)) {
                if (!empty($backup->adminEmail)) {
                    setOption('adminEmail', $backup->adminEmail);
                }
                setOption('leAccountKey', $backup->leAccountKey);
                setOption('leAccountID', $backup->leAccountID);
            }

        $db = getDB();
        $sharedAccounts = [];
        foreach ($backup->cert as $cert) {
            $domain = (isset($cert->domain) && is_string($cert->domain)) ? $cert->domain : '[undefined]';
            try {
                $idSshOld = strval($cert->idSsh);
                $sshAccount = $backup->ssh->{$idSshOld};
                if ($sshAccount->shared) {
                    if (isset($sharedAccounts[$idSshOld])) {
                        $idSshNew = $sharedAccounts[$idSshOld];
                    } else {
                        $stmt = $db->query('SELECT idSsh FROM ssh WHERE shared=1 AND sharedName=?', $sshAccount->sharedName);
                        $row = $stmt->fetchAssoc('i');
                        if (empty($row)) {
                            $idSshNew = SSH::addAccount(true, $sshAccount->sharedName, $sshAccount->host, $sshAccount->port, $sshAccount->login, $sshAccount->authType, $sshAccount->authValue);
                        } else {
                            $idSshNew = $row['idSsh'];
                        }
                        $sharedAccounts[$idSshOld] = $idSshNew;
                    }
                } else {
                    $idSshNew = SSH::addAccount(false, '', $sshAccount->host, $sshAccount->port, $sshAccount->login, $sshAccount->authType, $sshAccount->authValue);
                }
                $idCert = Cert::addCert($cert->certDomain, $cert->certIssuer, $cert->privateKey, $cert->csr, $idSshNew, $cert->documentRoot);
                if (!empty($cert->exports) && is_array($cert->exports))
                    foreach ($cert->exports as $export) try {
                        Exports::addExport($idCert, $export->exporterName, (array)$export->options, $export->finalCommand);
                    } catch (Exception $e) {
                        $errors[] = "Не удалось добавить конфигурацию экспорта для домена $domain: " . $e->getMessage();
                    }
            } catch (Exception $e) {
                $errors[] = "Не удалось добавить сертификат домена $domain: " . $e->getMessage();
            }
        }
    }
}