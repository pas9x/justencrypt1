<?php

namespace app;

use \Exception;
use \ErrorMessage;

class Exports
{
    public static function listExports($pg = null, $pp = 25, $idCert = null)
    {
        $query = 'SELECT * FROM exports';
        if (is_int($idCert)) {
            $query .= ' WHERE idCert=' . $idCert;
        }
        $query .= ' ORDER BY idExport';
        if (is_int($pg)) {
            $pp = intval($pp);
            if ($pp < 1) throw new Exception('Invalid value of $pp argument');
            $from = ($pg - 1) * $pp;
            $query .= "LIMIT $from, $pp";
        }
        $stmt = dbQuery($query);
        $result = [];
        while ($row = $stmt->fetchAssoc(TABLE_MASK_EXPORTS)) {
            $export = $row;
            $export['options'] = empty($export['options']) ? [] : unserialize($export['options']);
            $result[] = $export;
        }
        return $result;
    }

    public static function getExport($idExport)
    {
        $idExport = intval($idExport);
        $stmt = dbQuery('SELECT * FROM exports WHERE idExport=?', $idExport);
        $result = $stmt->fetchAssoc(TABLE_MASK_EXPORTS);
        if (empty($result)) {
            throw new ErrorMessage("Конфигурация выгрузки #$idExport не найдена");
        }
        $result['options'] = empty($result['options']) ? [] : unserialize($result['options']);
        return $result;
    }

    public static function listExporters()
    {
        static $result;
        if (!is_array($result)) {
            $_result = [];
            $exportersDir = PRIVDIR . '/exporters';
            $fsNodes = scandir($exportersDir);
            foreach ($fsNodes as $exporterName) {
                if ($exporterName === '.' || $exporterName === '..') continue;
                $exporterDir = $exportersDir . '/' . $exporterName;
                if (!is_dir($exporterDir)) continue;
                if (!preg_match('/^[a-z][a-z0-9_]{0,99}$/i', $exporterName)) continue;
                $className = 'Exporter_' . $exporterName;
                $exporterTitle = $className::getTitle();
                $_result[$exporterName] = $exporterTitle;
            }
            $result = $_result;
        }
        return $result;
    }

    public static function addExport($idCert, $exporterName, array $exporterOptions = null, $finalCommand = '')
    {
        $idCert = intval($idCert);
        $cert = Cert::getCert($idCert);
        $exporters = self::listExporters();
        if (!isset($exporters[$exporterName])) {
            displayError('Экспортёр с указанным вами именем не найден');
        }
        if (is_null($exporterOptions)) {
            $exporterClass = 'Exporter_' . $exporterName;
            if (!class_exists($exporterClass, true)) {
                throw new Exception("Exporter class $exporterClass not found");
            }
            $exporterOptions = $exporterClass::optionsFromUserForm();
        }
        $finalCommand = strval($finalCommand);
        if (strlen($finalCommand) > 255) {
            throw new ErrorMessage('Длина команды не должна превышать 255 символов');
        }
        $db = getDB();
        $db->insert(
            'exports',
            null,
            $idCert,
            $exporterName,
            serialize($exporterOptions),
            $finalCommand,
            0,
            '',
            0
        );
        $idExport = $db->insertId();
        return $idExport;
    }

    public static function deleteExport($idExport)
    {
        $idExport = intval($idExport);
        dbQuery('DELETE FROM exports WHERE idExport=?', $idExport);
    }

    public static function doExport($idExport)
    {
        $idExport = intval($idExport);
        $export = self::getExport($idExport);
        $exporterClass = 'Exporter_' . $export['exporterName'];
        if (!class_exists($exporterClass, true)) {
            throw new Exception("Failed to do export #$idExport because class $exporterClass not found");
        }
        /** @var Exporter $exporter */
        $exporter = new $exporterClass($idExport);
        try {
            $exporter->export();
        } catch (Exception $e) {
            $msg = 'Выгрузка не выполнена из-за ошибки: ' . $e->getMessage();
            throw new ErrorMessage($msg);
        }
    }

    public static function saveExport($idExport, array $exporterOptions = null, $finalCommand = '')
    {
        $idExport = intval($idExport);
        $export = self::getExport($idExport);
        $exporterName = $export['exporterName'];
        $exporters = self::listExporters();
        if (!isset($exporters[$exporterName])) {
            displayError("Экспортёр $exporterName не найден");
        }
        if (is_null($exporterOptions)) {
            $exporterClass = 'Exporter_' . $exporterName;
            if (!class_exists($exporterClass, true)) {
                throw new Exception("Exporter class $exporterClass not found");
            }
            $exporterOptions = $exporterClass::optionsFromUserForm();
        }
        $finalCommand = strval($finalCommand);
        if (strlen($finalCommand) > 255) {
            throw new ErrorMessage('Длина команды не должна превышать 255 символов');
        }
        dbQuery(
            'UPDATE exports SET options=?, finalCommand=? WHERE idExport=?',
            serialize($exporterOptions),
            $finalCommand,
            $idExport
        );
    }
}