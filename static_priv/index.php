<?php

(@include __DIR__ . '/bootstrap.php') or die('Bootstrap error #20');

$get = installed() ? param('get', 'webui') : 'install';

if (!preg_match('/^[a-z0-9]{1,25}$/i', $get)) {
    throw new Exception('Invalid format of get-parameter: ' . $get);
}

$getScript = PRIVDIR . "/get_$get/go.php";
if (!file_exists($getScript)) {
    throw new Exception('Invalid value of get-parameter: ' . $get);
}

require $getScript;
