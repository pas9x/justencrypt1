<?php

require PRIVDIR . '/bootstrap_html.php';
require PRIVDIR . '/get_webui/mod/WebuiModule.php';
require PRIVDIR . '/get_webui/mod/WebuiModuleAdmin.php';

$modLoader = function($className) {
    if (!preg_match('/^Mod_([a-z0-9_]{1,100})$/i', $className, $matches)) {
        return;
    }
    $modName = $matches[1];
    $classFile = PRIVDIR . "/get_webui/mod/$modName/$className.php";
    if (!file_exists($classFile)) {
        return;
    }
    require $classFile;
    if (!class_exists($className, false)) {
        throw new Exception("Module file $classFile doesn't contains class $className");
    }
};
spl_autoload_register($modLoader);

$modName = param('mod', 'index');
$func = param('func', 'index');

if (!preg_match('/^[a-z0-9_]{1,100}$/i', $modName)) {
    displayError('Значение параметра mod не соответствует требуемому формату');
}

$className = "Mod_$modName";
if (!class_exists($className)) {
    displayError("Модуль $modName не существует");
}

/** @var WebuiModule $mod*/
$mod = new $className;
$mod->processRequest($func);
