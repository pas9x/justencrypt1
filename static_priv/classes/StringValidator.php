<?php

class StringValidator {
    protected static $initialized = false;
    protected static $checkers;
    protected static $filters;

    // --- Checkers

    public static function check($checkerName, $str) {
        if (!self::checkerExists($checkerName)) throw new Exception("No such checker: $checkerName");
        $checker = self::$checkers[$checkerName][0];
        return $checker($str);
    }

    public static function checkName($name) {
        if (!preg_match('/^[a-zA-Z0-9\_\.\-]{2,100}$/', $name)) throw new Exception("Invalid validator name: $name");
    }

    public static function checkerExists($checkerName) {
        self::checkName($checkerName);
        return isset(self::$checkers[$checkerName]);
    }

    public static function addChecker($checkerName, Closure $checker) {
        self::addCheckerInternal($checkerName, $checker, false);
    }

    public static function addCheckerInternal($checkerName, Closure $checker, $builtIn) {
        if (self::checkerExists($checkerName)) throw new Exception("Checker $checkerName already exist");
        self::$checkers[$checkerName] = array($checker, $builtIn);
    }

    public static function removeChecker($checkerName) {
        if (!self::checkerExists($checkerName)) throw new Exception("Checker $checkerName already exist");
        $checker = self::$checkers[$checkerName];
        if ($checker[1]) throw new Exception("You cannot remove built-in checker");
        unset(self::$checkers[$checkerName]);
    }

    public static function listCheckers() {
        return array_keys(self::$checkers);
    }

    // --- Filters

    public static function filter($filterName, $str) {
        if (!self::filterExists($filterName)) throw new Exception("No such filter: $filterName");
        $filter = self::$filters[$filterName][0];
        return $filter($str);
    }

    public static function filterExists($filterName) {
        self::checkName($filterName);
        return isset(self::$filters[$filterName]);
    }

    public static function addFilter($filterName, Closure $filter) {
        self::addfilterInternal($filterName, $filter, false);
    }

    public static function addFilterInternal($filterName, Closure $filter, $builtIn) {
        if (self::filterExists($filterName)) throw new Exception("Filter $filterName already exist");
        self::$filters[$filterName] = array($filter, $builtIn);
    }

    public static function removeFilter($filterName) {
        if (!self::filterExists($filterName)) throw new Exception("Filter $filterName already exist");
        $filter = self::$filters[$filterName];
        if ($filter[1]) throw new Exception("You cannot remove built-in filter");
        unset(self::$filters[$filterName]);
    }

    public static function listFilters() {
        return array_keys(self::$filters);
    }

    // -- Initialization

    public  static function init() {
        if (self::$initialized) return;
        self::$initialized = true;
        self::$checkers = array();
        self::$filters = array();

        // identifier, min:1, max:19
        $checker = function($str) {
            return preg_match('/^[a-z][a-z0-9]{1,19}$/i', $str)===1;
        };
        self::addCheckerInternal('identifier', $checker, true);

        // name, min:1, max:100
        $checker = function($str) {
            return preg_match('/^[a-zA-Z0-9\_\.\-]{1,100}$/', $str)===1;
        };
        self::addCheckerInternal('name', $checker, true);

        // login, min:3, max:16
        $checker = function($str) {
            return preg_match('/^[a-z][a-z0-9]{2,15}$/i', $str)===1;
        };
        self::addCheckerInternal('login', $checker, true);

        // domain, min:4, max:100
        $checker = function($str) {
            if (!preg_match('/^[a-zA-Z0-9\.\-]{4,100}$/', $str)) return false;
            $zones = explode('.', $str);
            $cnt = count($zones);
            if ($cnt<2) return false;
            $firstZone = $zones[$cnt-1];
            unset($zones[$cnt-1]);
            foreach ($zones as $zone) {
                if (!preg_match('/^[a-zA-Z0-9]/', $zone)) return false;
                if (!preg_match('/[a-zA-Z0-9]$/', $zone)) return false;
                if (is_int(strpos($zone, '--'))) return false;
            }
            if (!preg_match('/^[a-zA-Z]{2,20}$/', $firstZone)) return false;
            return true;
        };
        self::addCheckerInternal('domain', $checker, true);

        // email, min:6, max:100
        $checker = function($str) {
            if (!preg_match('/^[a-zA-Z0-9\_\.\-\@]{6,100}$/', $str)) return false;
            $parts = explode('@', $str);
            if (count($parts) !== 2) return false;
            list($user, $host) = $parts;
            if (!StringValidator::check('domain', $host)) return false;
            if (!preg_match('/^[a-zA-Z0-9]/', $user)) return false;
            if (!preg_match('/[a-zA-Z0-9]$/', $user)) return false;
            if (preg_match('/[\_\.\-]{2,}/', $user)) return false;
            return true;
        };
        self::addCheckerInternal('email', $checker, true);

        // ipv4, min:7, max:15
        $checker = function($str) {
            if (!preg_match('/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/', $str, $matches)) {
                return false;
            }
            $byte1 = intval($matches[1]);
            $byte2 = intval($matches[2]);
            $byte3 = intval($matches[3]);
            $byte4 = intval($matches[4]);
            if ($byte1>255 || $byte2>255 || $byte3>255 || $byte4>255) {
                return false;
            }
            return true;
        };
        self::addCheckerInternal('ipv4', $checker, true);

        // ipv6, min:2, max:39
        $checker = function($str) {
            $str = strval($str);
            $ok = filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
            return ($ok === $str);
        };
        self::addCheckerInternal('ipv6', $checker, true);

        $filter = function($str) {
            if (!preg_match('/^(https?\:\/\/)?([a-z0-9\.\-]{4,50})(\/.*)?$/i', $str, $matches)) {
                return null;
            }
            $schema = strtolower($matches[1]);
            $domain = preg_replace('/^www\./', '', strtolower($matches[2]));
            $uri = isset($matches[3]) ? strval($matches[3]) : '';
            if ($schema === '') {
                $schema = 'http://';
            }
            if (!self::check('domain', $domain)) {
                return null;
            }
            if ($uri === '') {
                $uri = '/';
            }
            $result = $schema . $domain . $uri;
            return $result;
        };
        self::addFilterInternal('url', $filter, true);

    }
}

StringValidator::init();