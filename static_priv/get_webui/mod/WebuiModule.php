<?php

abstract class WebuiModule {
    public abstract function getName();
    public abstract function getTitle();

    protected $exports = [];

    public function __construct() {
        $methods = get_class_methods(get_class($this));
        if (!is_array($methods)) {
            return;
        }
        foreach ($methods as $method) {
            if (!preg_match('/^func_(.+)$/', $method, $match)) continue;
            $func = $match[1];
            $this->exports[] = $func;
        }
    }

    public function processRequest($func) {
        if (!preg_match('/^[a-z0-9_]{1,100}$/i', $func)) {
            displayError('Имя функции не соответствует требуемому формату');
        }
        if (!in_array($func, $this->exports)) {
            displayError('Этот модуль не имеет функции ' . $func);
        }
        $method = 'func_' . $func;
        $this->$method();
    }

    public static function modLink($modName, $func = null, array $parameters = []) {
        $result = '/index.php?get=webui&mod=' . rawurlencode($modName);
        if (!is_null($func)) {
            $result .= '&func=' . rawurlencode($func);
        }
        foreach ($parameters as $paramName => $paramValue) {
            $paramName = strval($paramName);
            $paramValue = strval($paramValue);
            if ($paramName === '') {
                throw new Exception('One of parameters has empty name');
            }
            $result .= '&' . rawurlencode($paramName);
            if ($paramValue !== '') {
                $result .= '=' . rawurlencode($paramValue);
            }
        }
        return $result;
    }

    public function selfLink($func = null, array $parameters = []) {
        return self::modLink($this->getName(), $func, $parameters);
    }

    public function getDirectory() {
        return PRIVDIR . '/get_webui/mod/' . $this->getName();
    }
}