<?php

namespace app;

use \Exception;

class Cron
{
    public static function startDailyJobs(callable $stdout, callable $stderr)
    {
        self::autoprolong($stdout, $stderr);
    }

    public static function autoprolong(callable $stdout, callable $stderr)
    {
        $prolongDaysEarly = getOption('prolongDaysEarly');
        $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $dueTo = $today + ($prolongDaysEarly * SEC_DAY);
        $db = getDB();
        $stmt = $db->query('SELECT idCert, domain FROM cert WHERE expire <= ?', date('Y-m-d', $dueTo));
        $expiringList = $stmt->fetchAssocAll('i,s');
        $expiringCount = count($expiringList);
        if ($expiringCount < 1) {
            $stdout("No certificates to prolong");
            return;
        }
        $stdout("$expiringCount expiring certificates found. Reissue in progress...");
        $reissuedCount = 0;
        $failedCount = 0;
        foreach ($expiringList as $cert) {
            $idCert = $cert['idCert'];
            $domain = $cert['domain'];
            try {
                Cert::reissueCert($idCert);
                $reissuedCount++;
                $exports = Exports::listExports(null, null, $idCert);
                foreach ($exports as $export) try {
                    Exports::doExport($export['idExport']);
                } catch (Exception $e) {
                    errorLog("Certificate #$idCert ($domain), exporting failed #$export[idExport]", $e);
                    $stderr("Certificate #$idCert ($domain), exporting failed #$export[idExport]: " . $e->getMessage());
                }
            } catch (Exception $e) {
                $failedCount++;
                $stderr("Certificate #$idCert ($domain), reissue failed: " . $e->getMessage());
                errorLog("Failed to reissue certificate #$idCert ($domain)", $e);
            }
        }
        $stdout('Reissue complete.');
        if ($reissuedCount > 0) {
            $stdout("$reissuedCount certificates successfully reissued.");
        }
        if ($failedCount > 0) {
            $stderr("$failedCount was not reissued due to errors.");
        }
    }
}