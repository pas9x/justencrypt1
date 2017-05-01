<?php

require __DIR__ . '/bootstrap.php';

use \app\Cron;

$pidFile = DATADIR . '/cron.pid';
if (file_exists($pidFile)) {
    $foreignPid = trim(file_get_contents($pidFile));
    $foreignPid = ($foreignPid === '') ? null : intval($foreignPid);
    try {
        $proclist = processList();
    } catch (Exception $e) {
        debugLog('processList() failed: ' . $e->getMessage());
    }
    if (isset($proclist[$foreignPid]['command'])) {
        $command = $proclist[$foreignPid]['command'];
        if (preg_match('/php/i', $command)) {
            fatal("Another process with pid=$foreignPid running now\n");
        }
    }
}

file_put_contents($pidFile, getmypid());

$stdout = function($msg) {
    out('[' . date('d.m.Y H:i:s') . '] ' . trim($msg) . "\n");
};

$stderr = function($msg) {
    err('[' . date('d.m.Y H:i:s') . '] ' . trim($msg) . "\n");
};

Cron::startDailyJobs($stdout, $stderr);

unlink($pidFile);
