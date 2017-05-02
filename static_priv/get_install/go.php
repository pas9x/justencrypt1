<?php

function putPage($body) {
    $html = file_get_contents(PRIVDIR . '/get_install/template.html');
    $html = str_replace('{body}', $body, $html);
    header('Content-Type: text/html; charset=utf-8', true);
    stop($html);
}

if (installed()) {
    putPage('Панель уже установлена. Если нужно переустановить панель, удалите файл userdata/db.sqlite. Хорошо подумайте прежде чем удалить этот файл, в нём хранятся все сертификаты и ssh-аккаунты.');
}

if (!checkPost('pass,email,leAccountKey')) {
    $form = file_get_contents(PRIVDIR . '/get_install/form.html');
    putPage($form);
}

$pass = post('pass');
$email = post('email');
$leAccountKey = post('leAccountKey');

if (!checkLength($pass, 8, 32)) {
    putPage('Пароль должен иметь длину от 8 до 32 символов');
}

if (!StringValidator::check('email', $email)) {
    putPage('Адрес e-mail указан неверно');
}

$dbFile = DATADIR . '/db.sqlite';
if (!file_exists($dbFile)) {
    touch($dbFile);
}

$db = getDB();

$fsNodes = scandir(PRIVDIR . '/get_install/tables');
foreach ($fsNodes as $fsNode) {
    $tablePath = PRIVDIR . '/get_install/tables/' . $fsNode;
    if ($fsNode === '.' || $fsNode === '..') {
        continue;
    }
    if (!is_file($tablePath)) {
        continue;
    }
    if (!preg_match('/^(([0-9]+_)?.+)\.sql$/', $fsNode, $match)) {
        continue;
    }
    $tableName = $match[1];
    $createTable = file_get_contents($tablePath);
    $db->query($createTable);
}

$configFile = DATADIR . '/config.php';
if (!file_exists($configFile)) {
    $saltPriv = keygen(32);
    $saltPub = keygen(32);
    $cryptKey = keygen(32);
    $keys = <<<PHP
<?php
define('SALT_PRIV', '$saltPriv');
define('SALT_PUB', '$saltPub');
define('CRYPT_KEY', '$cryptKey');

PHP;
    file_put_contents($configFile, $keys);
    require $configFile;
}

setOption('authInterval', 10);
setOption('adminEmail', $email);
setOption('adminPass', sha1priv($pass));
setOption('sessionLifetime', 600);
setOption('prolongDaysEarly', 3);
$defaultCsrTemplate = [
    'countryName' => 'RU',
    'stateOrProvinceName' => 'Moscow',
    'localityName' => 'Moscow',
    'organizationName' => 'Private Person',
    'organizationalUnitName' => 'IT'
];
setOption('defaultCsrTemplate', $defaultCsrTemplate);

putPage("Установка панели успешно завершена. Теперь вы можете войти в панель с вашим паролем.");
