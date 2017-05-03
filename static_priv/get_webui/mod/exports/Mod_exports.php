<?php

use \app\Cert;
use \app\Exports;


class Mod_exports extends WebuiModuleAdmin {
    public function __construct()
    {
        parent::__construct();
        $this->templateDirectories[] = PRIVDIR . '/exporters';
    }

    public function getName()
    {
        return 'exports';
    }

    public function getTitle()
    {
        return 'Конфигурации выгрузки';
    }

    public function func_certExports()
    {
        $idCert = intval(get('idCert'));
        $cert = Cert::getCert($idCert);
        $certLink = self::modLink('cert', 'showCert', compact('idCert'));
        $exporters = Exports::listExporters();
        $exports = Exports::listExports(null, 25, $idCert);
        foreach ($exports as &$export) {
            $idExport = $export['idExport'];
            $export['exporterTitle'] = isset($exporters[$export['exporterName']]) ? $exporters[$export['exporterName']] : ('[' . $export['exporterName'] . ']');
            $export['sync'] = ($export['lastCertHash'] === $cert['certDomainHash']);
            $export['status'] = empty($export['lastError']) ? 'OK' : ('Error: ' . utf8_encode($export['lastError']));
            $export['deleteLink'] = $this->selfLink('deleteExport', compact('idExport'));
            $export['editLink'] = $this->selfLink('editExport', compact('idExport'));
            $export['startLink'] = $this->selfLink('doExport', compact('idExport'));
        }

        $this->signs += compact('idCert', 'cert', 'certLink', 'exports', 'exporters');
        $this->template = 'certExports';
        $this->title = 'Конфигурации выгрузки сертификата ' . $cert['domain'];
        $this->display();
    }

    public function func_newExportForm()
    {
        $exporterName = param('exporterName');
        $exporters = Exports::listExporters();
        if (!isset($exporters[$exporterName])) {
            displayError('Экспортёр с указанным вами именем не найден');
        }
        $idCert = intval(param('idCert'));
        $exporterClass = 'Exporter_' . $exporterName;
        $this->template = 'newExportForm';
        $this->title = 'Настройка выгрузки для ' . $exporters[$exporterName];
        $this->signs['action'] = $this->selfLink('addExport');
        $this->signs['exporterTitle'] = $exporters[$exporterName];
        $this->signs['exporterName'] = $exporterName;
        $this->signs['exampleFinalCommand'] = $exporterClass::exampleFinalCommand();
        $this->signs['idCert'] = $idCert;
        $this->signs['cert'] = Cert::getCert($idCert);
        $this->signs['exportsLink'] = $this->selfLink('certExports', compact('idCert'));
        $this->display();
    }

    public function func_addExport()
    {
        $exporterName = post('exporterName');
        $idCert = intval(param('idCert'));
        $idExport = Exports::addExport($idCert, $exporterName, null, post('finalCommand'));
        try {
            Exports::doExport($idExport);
        } catch (Exception $e) {
            Exports::deleteExport($idExport);
            displayError('Конфигурация выгрузки не добавлена из-за ошибки: ' . $e->getMessage());
        }
        $backLink = $this->selfLink('certExports', compact('idCert'));
        displayOK('Конфигурация выгрузки успешно добавлена', $backLink, 'Список конфигураций');
    }

    public function func_doExport()
    {
        $idExport = intval(get('idExport'));
        $export = Exports::getExport($idExport);
        Exports::doExport($idExport);
        $backLink = $this->selfLink('certExports', ['idCert' => $export['idCert']]);
        displayOK('Выгрузка сертификата завершена успешно', $backLink, 'Список конфигураций');
    }

    public function func_editExport()
    {
        $idExport = intval(get('idExport'));
        $export = Exports::getExport($idExport);
        $idCert = $export['idCert'];
        $exporters = Exports::listExporters();
        $exporterName = $export['exporterName'];
        $this->signs += $export;
        $this->signs['action'] = $this->selfLink('saveExport');
        $this->signs['exporterName'] = $exporterName;
        $this->signs['exporterTitle'] = $exporters[$exporterName];
        $this->signs['cert'] = Cert::getCert($export['idCert']);
        $this->signs['exportsLink'] = $this->selfLink('certExports', compact('idCert'));
        $this->template = 'editExportForm';
        $this->title = 'Настройка выгрузки для ' . $exporters[$exporterName];
        $this->display();
    }

    public function func_saveExport()
    {
        $idExport = intval(post('idExport'));
        $export = Exports::getExport($idExport);
        Exports::saveExport($idExport, null, post('finalCommand'));
        $backLink = $this->selfLink('certExports', ['idCert' => $export['idCert']]);
        displayOK('Конфигурация выгрузки сохранена', $backLink, 'Список конфигураций');
    }
}