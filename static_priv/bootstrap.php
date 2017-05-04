<?php

ini_set('display_errors', 'off');
error_reporting(E_ALL);
define('BASEDIR', dirname(__DIR__));
ini_set('error_log', BASEDIR . '/userdata/error.log');
ini_set('log_errors', 'on');

if (version_compare('5.4', PHP_VERSION) === 1) {
    @header('Content-Type: text/plain');
    echo "JustEncrypt panel requires php version 5.4 or higher.\n";
    echo "Your php version: " . PHP_VERSION . "\n";
    exit;
}

$modulesRequired = array(
    'curl',
    'date',
    'fileinfo',
    'filter',
    'hash',
    // 'iconv',
    'json',
    'mbstring',
    'mcrypt',
    'openssl',
    'PDO',
    'pdo_sqlite',
    'SimpleXML',
    'zip'
);
$modulesAbsent = array();
foreach ($modulesRequired as $module) {
    if (!extension_loaded($module)) {
        $modulesAbsent[] = $module;
    }
}
if (count($modulesAbsent) > 0) {
    @header('Content-Type: text/plain');
    echo "Following php modules not found: " . implode(', ', $modulesAbsent) . "\n";
    echo "JustEncrypt panel cannot work without these modules. Install it first.\n";
    exit;
}
unset($modulesRequired, $modulesAbsent);

if (file_exists(__DIR__ . '/version.php')) {
    require_once __DIR__ . '/version.php';
}
if (!defined('RELEASE_VERSION')) {
    define('RELEASE_VERSION', 'dev');
}
if (!defined('RELEASE_COMMIT')) {
    define('RELEASE_COMMIT', '');
}
if (!defined('RELEASE_TIMESTAMP')) {
    define('RELEASE_TIMESTAMP', 0);
}
define('PUBDIR', BASEDIR . '/static_pub');
define('PRIVDIR', BASEDIR . '/static_priv');
define('DATADIR', BASEDIR . '/userdata');
define('TMPDIR', PRIVDIR . '/tmp');
define('SEC_MINUTE', 60);
define('SEC_HOUR', SEC_MINUTE * 60);
define('SEC_DAY', SEC_HOUR * 24);
define('SEC_WEEK', SEC_DAY * 7);
define('SEC_MONTH', SEC_WEEK * 4);
define('SEC_YEAR', SEC_DAY * 365);
define('SCRIPT_START', time());
define('SCRIPT_STARTEX', microtime(true));
define('SITE_URI', '/');
(include PRIVDIR . '/base/functions.php') or die("Bootstrap error #30\n");
(include PRIVDIR . '/composer/vendor/autoload.php') or die("Bootstrap error #31\n");

loadClass('UnexpectedError');
loadClass('ErrorMessage');

set_exception_handler('exceptionHandler');
set_error_handler('errorHandler');
spl_autoload_register('loadClass', true);

date_default_timezone_set('Etc/UTC');
mb_internal_encoding('UTF-8');

$configFile = DATADIR . '/config.php';
if (file_exists($configFile)) {
    require $configFile;
}

foreach (scandir(PRIVDIR . '/inc_auto') as $node) {
    $path = PRIVDIR . "/inc_auto/$node";
    if (!is_file($path)) continue;
    if (!preg_match('/\.php$/i', $node)) continue;
    require $path;
}

$exportersClassLoader = function($className) {
    if (!preg_match('/^Exporter_([a-zA-Z][a-zA-Z0-9_]{0,99})$/', $className, $matches)) {
        return;
    }
    $exporterName = $matches[1];
    $classFile = PRIVDIR . "/exporters/$exporterName/exporter.php";
    if (file_exists($classFile)) {
        require_once $classFile;
    }
};
spl_autoload_register($exportersClassLoader);
unset($exportersClassLoader);

define('ZIP_ALLOW_PASS', method_exists('ZipArchive', 'setPassword'));
