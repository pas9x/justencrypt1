<?php

function loadClass($className)
{
    $fileName = str_replace('\\', '/', $className);
    $fileName = PRIVDIR . "/classes/$fileName.php";
    if (file_exists($fileName)) {
        ob_start();
        include_once $fileName;
        ob_end_clean();
    }
}

function errorConstants()
{
    static $constants = null;
    if (is_null($constants)) {
        $constants = array();
        if (defined('E_ERROR')) $constants[E_ERROR] = 'E_ERROR';
        if (defined('E_WARNING')) $constants[E_WARNING] = 'E_WARNING';
        if (defined('E_PARSE')) $constants[E_PARSE] = 'E_PARSE';
        if (defined('E_NOTICE')) $constants[E_NOTICE] = 'E_NOTICE';
        if (defined('E_CORE_ERROR')) $constants[E_CORE_ERROR] = 'E_CORE_ERROR';
        if (defined('E_CORE_WARNING')) $constants[E_CORE_WARNING] = 'E_CORE_WARNING';
        if (defined('E_COMPILE_ERROR')) $constants[E_COMPILE_ERROR] = 'E_COMPILE_ERROR';
        if (defined('E_COMPILE_WARNING')) $constants[E_COMPILE_WARNING] = 'E_COMPILE_WARNING';
        if (defined('E_USER_ERROR')) $constants[E_USER_ERROR] = 'E_USER_ERROR';
        if (defined('E_USER_WARNING')) $constants[E_USER_WARNING] = 'E_USER_WARNING';
        if (defined('E_USER_NOTICE')) $constants[E_USER_NOTICE] = 'E_USER_NOTICE';
        if (defined('E_STRICT')) $constants[E_STRICT] = 'E_STRICT';
        if (defined('E_RECOVERABLE_ERROR')) $constants[E_RECOVERABLE_ERROR] = 'E_RECOVERABLE_ERROR';
        if (defined('E_DEPRECATED')) $constants[E_DEPRECATED] = 'E_DEPRECATED';
        if (defined('E_USER_DEPRECATED')) $constants[E_USER_DEPRECATED] = 'E_USER_DEPRECATED';
        if (defined('E_ALL')) $constants[E_ALL] = 'E_ALL';
    }
    return $constants;
}

function errorHandler($errno, $errstr, $errfile = null, $errline = null, $errcontext = null)
{
    if (error_reporting() === 0) {
        return true;
    }
    throw new UnexpectedError($errno, $errstr, $errfile, $errline, $errcontext);
}

function exceptionHandler(Exception $e)
{
    if ($e instanceof ErrorMessage) {
        if (function_exists('displayError')) {
            displayError($e->getMessages());
            stop();
        }
    }

    $message = '';
    if ($e instanceof UnexpectedError) {
        $message .= 'UnexpectedError';
        $errstr = trim($e->errstr);
        if ($errstr !== '') {
            $message .= ": $errstr\n";
        } else {
            $message .= " (without message)\n";
        }
        $errors = errorConstants();
        $message .= 'Code: ' . $e->errno;
        if (isset($errors[$e->errno])) {
            $message .= ' (' . $errors[$e->errno] . ')';
        }
        $message .= "\n";
        $message .= empty($e->errfile) ? "File: unknown\n" : "File: {$e->errfile}\n";
        $message .= empty($e->errline) ? "Line: unknown\n" : "Line: {$e->errline}\n";

    } else {
        $message .= 'Uncaught ' . get_class($e) . ': ' . trim($e->getMessage()) . "\n";
        $code = $e->getCode();
        $message .= 'Code: ' . $code;
        if (isset($errors[$code])) {
            $message .= ' (' . $errors[$code] . ')';
        }
        $message .= "\n";
        $message .= 'File: ' . $e->getFile() . "\n";
        $message .= 'Line: ' . $e->getLine() . "\n";
    }

    if (isset($_SERVER['REQUEST_METHOD'])) {
        $message .= $_SERVER['REQUEST_METHOD'];
        if (isset($_SERVER['REQUEST_URI'])) {
            $message .= ' ' . $_SERVER['REQUEST_URI'];
        }
        $message .= "\n";
    }

    if (isset($_SERVER['REMOTE_ADDR'])) {
        $message .= 'Remote IP: ' . $_SERVER['REMOTE_ADDR'] . "\n";
    }

    if (!empty($_SERVER['HTTP_REFERER'])) {
        $message .= 'Referer: ' . $_SERVER['HTTP_REFERER'] . "\n";
    }

    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        $message .= 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'] . "\n";
    }

    if (!empty($_POST)) {
        $message .= 'Post parameters: ';
        $message .= trim(print_r($_POST, true));
        $message .= "\n";
    }

    $message .= "Stacktrace:\n";
    $message .= trim($e->getTraceAsString());

    @errorLog($message);

    $printer = errorPrinter();
    if (is_callable($printer)) {
        $printer($message);
    } elseif (outputClean()) {
        http_response_code(500);
    }
    fatal();
}

function writeLog($logfile, $message, $additionalInfo = null)
{
    static $files = [];
    if (isset($files[$logfile])) {
        $fh = $files[$logfile];
    } else {
        $fh = @fopen(DATADIR . '/' . $logfile, 'a');
        if (!is_resource($fh)) return false;
        $files[$logfile] = $fh;
    }
    $message = trim($message);
    $message = '[' . date('d.m.Y H:i:s') . "] $message\n";
    if ($additionalInfo instanceof Exception) {
        $message .= "Additional info:\n" . trim($additionalInfo->__toString());
    } elseif (!is_null($additionalInfo)) {
        $message .= "Additional info:\n" . trim(print_r($additionalInfo, true));
    }
    $message .= "\n\n";
    $len = strlen($message);
    if (@fwrite($fh, $message) === $len) {
        return true;
    } else {
        return false;
    }
}

function errorLog($message, $additionalInfo = null)
{
    return writeLog('error.log', $message, $additionalInfo);
}

function infoLog($message, $additionalInfo = null)
{
    return writeLog('info.log', $message, $additionalInfo);
}

function securityLog($message, $additionalInfo = null)
{
    return writeLog('security.log', $message, $additionalInfo);
}

function debugLog($message, $additionalInfo = null)
{
    if (defined('DEBUG') && DEBUG) {
        return writeLog('debug.log', $message, $additionalInfo);
    } else {
        return false;
    }
}

function errorPrinter(callable $newPrinter = null)
{
    static $printer = null;
    if (!is_null($newPrinter)) {
        if (is_callable($newPrinter)) {
            $printer = $newPrinter;
        } else {
            throw new Exception('$newPrinter is not callable');
        }
    }
    return $printer;
}

function outputClean()
{
    $level = ob_get_level();
    for ($j = 0; $j < $level; $j++) ob_end_clean();
    return !headers_sent();
}

function escapeHTML($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function out($text)
{
    if (!is_scalar($text)) {
        throw new InvalidArgumentException('Invalid type of $text argument: ' . gettype($text));
    }
    if (defined('SILENT') && SILENT) {
        return;
    }
    echo $text;
}

function err($text)
{
    if (!is_scalar($text)) {
        throw new InvalidArgumentException('Invalid type of $text argument: ' . gettype($text));
    }
    if (defined('SILENT') && SILENT) {
        return;
    }
    if (defined('STDERR')) {
        fwrite(STDERR, $text);
    } else {
        echo $text;
    }
}

function stop($text = '')
{
    if ($text !== '') {
        out($text);
    }
    exit(0);
}

function fatal($text = '')
{
    if ($text !== '') {
        err($text);
    }
    exit(1);
}

function createObject($className, $constructorArguments = array())
{
    if (!class_exists($className)) {
        throw new Exception("Class $className does not exist");
    }
    $ref = new ReflectionClass($className);
    $result = $ref->newInstanceArgs($constructorArguments);
    if (!is_object($result)) {
        throw new Exception('Failed to instantiate object');
    }
    return $result;
}

/**
 * @return DBConnection
 */
function getDB($close = false)
{
    static $db = null;
    if ($close) {
        $db->close();
        $db = null;
        return null;
    } else {
        if (is_null($db) || $db->closed()) {
            $db = new DBConnection(['sqlite:' . DATADIR . '/db.sqlite']);
        }
        return $db;
    }
}

function sendmail($from, $to, $subject, $body, array $headers = []) {
    $headers[] = 'Content-Type: text/plain; charset=utf-8';
    if (empty($from)) {
        $headers[] = 'From: ' . SENDMAIL_FROM;
    }
    else {
        $headers[] = 'From: ' . $from;
    }
    $headers = trim(implode("\n", $headers));

    if (defined('TEST_MAIL') && TEST_MAIL) {
        $entry = "sendmail():\n";
        $entry .= "From: $from\n";
        $entry .= "To: $to\n";
        $entry .= "Subject: $subject\n";
        $entry .= "Body:\n";
        $entry .= $body;
        logInfo($entry);
        return;
    }

    $ok = mail($to, $subject, $body, $headers);
    if ($ok !== true) {
        throw new Exception("Failed to sendmail() from $from to $to with subject '$subject'");
    }
}

function sendmailTemplate($from, $to, $templateName, array $signs = [], array $headers =[])
{
    $mailSubject = '[no subject]';
    $signs['subject'] = function($subject) use(&$mailSubject) {
        $mailSubject = $subject;
    };
    $tpl = new Template(PRIVDIR . '/templates/mail');
    $body = $tpl->render($templateName, $signs);
    $result = sendmail($from, $to, $mailSubject, $body, $headers);
    return $result;
}

function redirect($url)
{
    header("Location: $url", true);
    stop();
}

function checkParameters(&$parameters, $mask)
{
    $items = trim($mask);
    if ($items === '') {
        return false;
    }
    $items = explode(',', $items);
    foreach ($items as $item) {
        $item = trim($item);
        if ($item === '') {
            throw new Exception('Invalid check mask');
        }
        if (!isset($parameters[$item])) {
            return false;
        }
    }
    return true;
}

function checkGet($mask)
{
    return checkParameters($_GET, $mask);
}

function checkPost($mask)
{
    return checkParameters($_POST, $mask);
}

function haveParam($parameterName)
{
    return isset($_GET[$parameterName]) || isset($_POST[$parameterName]);
}

function get($name, $defaultValue = null)
{
    if (isset($_GET[$name])) {
        return $_GET[$name];
    }
    $hasDefault = func_num_args() > 1;
    if ($hasDefault) {
        return $defaultValue;
    }
    throw new Exception("GET-parameter '$name' not present");
}

function post($name, $defaultValue = null)
{
    if (isset($_POST[$name])) {
        return $_POST[$name];
    }
    $hasDefault = func_num_args() > 1;
    if ($hasDefault) {
        return $defaultValue;
    }
    throw new Exception("POST-parameter '$name' not present");
}

function param($name, $defaultValue = null)
{
    if (isset($_POST[$name])) {
        return $_POST[$name];
    }
    if (isset($_GET[$name])) {
        return $_GET[$name];
    }
    $hasDefault = func_num_args() > 1;
    if ($hasDefault) {
        return $defaultValue;
    }
    throw new Exception("GET/POST-parameter '$name' not present");
}

function extractParameters(&$parameters, $mask)
{
    $items = explode(',', $mask);
    $result = array();
    foreach ($items as $item) {
        $item = trim($item);
        if (is_null($item)) {
            continue;
        }
        if (preg_match('/\s*\?$/', $item)) {
            $optional = true;
            $item = preg_replace('/\s*\?$/', '', $item);
        } else {
            $optional = false;
        }
        $item = explode('>', $item);
        $inputName = trim($item[0]);
        $convertTo = isset($item[1]) ? trim($item[1]) : 's';
        $renamedName = isset($item[2]) ? trim($item[2]) : $inputName;
        if (!preg_match('/^[a-zA-Z0-9\_\.\-]{1,100}$/', $inputName)) {
            throw new Exception("Invalid input parameter name '$inputName' found in mask");
        }
        if (!preg_match('/^[a-zA-Z0-9\_\.\-]{1,100}$/', $renamedName)) {
            throw new Exception("Invalid renamed parameter name '$renamedName' found in mask");
        }
        if (isset($result[$renamedName])) {
            throw new Exception("Double output parameter name '$renamedName' found in mask");
        }
        if (isset($parameters[$inputName])) {
            $originalValue = &$parameters[$inputName];
        } else {
            if ($optional) {
                $originalValue = '';
            } else {
                throw new Exception("Input parameter not found: $inputName");
            }
        }
        if ($convertTo === 's' || $convertTo === '') {
            $value = strval($originalValue);
        } elseif ($convertTo === 'i') {
            $value = intval($originalValue);
        } elseif ($convertTo === 'f') {
            $value = floatval($originalValue);
        } elseif ($convertTo === 'h') {
            $value = escapeHTML($originalValue);
        } elseif ($convertTo === 'b') {
            $value = (boolean)$originalValue;
        } else {
            throw new Exception("Unsupported typecast '$convertTo' found in mask");
        }
        $result[$renamedName] = $value;
    }
    if (count($result) < 1) {
        throw new Exception('Empty result selection');
    }
    return $result;
}


function extractGet($mask)
{
    return extractParameters($_GET, $mask);
}


function extractPost($mask)
{
    return extractParameters($_POST, $mask);
}

/**
 * @return Options
 */
function getOptions()
{
    static $options = null;
    if (!($options instanceof Options)) {
        $options = new Options;
    }
    return $options;
}

function setOption($optionName, $optionValue) {
    $options = getOptions();
    $options->set($optionName, $optionValue);
}

function getOption($optionName, $defaultValue = null) {
    $options = getOptions();
    if (func_num_args() > 1) {
        return $options->get($optionName, $defaultValue);
    } else {
        return $options->get($optionName);
    }
}

function delOption($optionName) {
    $options = getOptions();
    $options->del($optionName);
}

/**
 * @return DBStatement
 */
function dbQuery()
{
    $db = getDB();
    $args = func_get_args();
    return call_user_func_array([$db, 'query'], $args);
}

function putDump($var, $verbose = false)
{
    if ($verbose) {
        ob_start();
        var_dump($var);
        $result = ob_get_clean();
    } else {
        $result = print_r($var, true);
    }
    out("\n\n<pre>\n");
    out(escapeHTML($result));
    stop("\n</pre>\n\n");
}

function checkLength($s, $min, $max)
{
    $s = strval($s);
    $len = mb_strlen($s, 'UTF-8');
    if ($len < $min) {
        return false;
    }
    if ($len > $max) {
        return false;
    }
    return true;
}

function keygen($len)
{
    if ($len < 0) {
        return false;
    }
    $result = '';
    for ($j = 0; $j < $len; $j++) {
        switch (rand(0, 2)):
            case 0:
                $result .= chr(rand(97, 122));
                break;
            case 1:
                $result .= chr(rand(65, 90));
                break;
            case 2:
                $result .= chr(rand(48, 57));
                break;
        endswitch;
    }
    return $result;
}

function xorize($sourceString, $repeatedString)
{
    $sourceString = strval($sourceString);
    $repeatedString = strval($repeatedString);
    $sourceLen = strlen($sourceString);
    $repeatedLen = strlen($repeatedString);
    if ($sourceLen < 1) {
        throw new Exception('Empty $sourceString');
    }
    if ($repeatedLen < 1) {
        throw new Exception('Empty $repeatedString');
    }
    $nRepeated = 0;
    for ($nSource = 0; $nSource < $sourceLen; $nSource++) {
        $codeSource = ord($sourceString[$nSource]);
        $codeRepeated = ord($repeatedString[$nRepeated]);
        $nRepeated++;
        if ($nRepeated === $repeatedLen) {
            $nRepeated = 0;
        }
        $sourceString[$nSource] = chr($codeSource ^ $codeRepeated);
    }
    return $sourceString;
}

function md5salt($str, $salt)
{
    return md5(xorize($str, $salt));
}

function sha1salt($str, $salt)
{
    return sha1(xorize($str, $salt));
}

function md5rand()
{
    return md5(keygen(32));
}

function sha1rand()
{
    return sha1(keygen(40));
}

function md5priv($str)
{
    if (!defined('SALT_PRIV')) throw new Exception("SALT_PRIV constant didn't defined. Probable this app was not installed properly.");
    return md5salt($str, SALT_PRIV);
}

function md5pub($str)
{
    if (!defined('SALT_PUB')) throw new Exception("SALT_PUB constant didn't defined. Probable this app was not installed properly.");
    return md5salt($str, SALT_PUB);
}

function sha1priv($str)
{
    if (!defined('SALT_PRIV')) throw new Exception("SALT_PRIV constant didn't defined. Probable this app was not installed properly.");
    return sha1salt($str, SALT_PRIV);
}

function sha1pub($str)
{
    if (!defined('SALT_PUB')) throw new Exception("SALT_PUB constant didn't defined. Probable this app was not installed properly.");
    return sha1salt($str, SALT_PUB);
}

function encrypt($data, $key = null)
{
    if (is_null($key)) {
        if (!defined('CRYPT_KEY')) throw new Exception("CRYPT_KEY constant didn't defined. Probable this app was not installed properly.");
        else $key = CRYPT_KEY;
    }
    if (!is_scalar($data)) {
        throw new Exception('Invalid type of $data argument: ' . gettype($data));
    }
    if (!is_string($data)) {
        $data = strval($data);
    }
    $dataSize = strlen($data);
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_RANDOM);
    $encryptedData = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_CBC, $iv);
    $struct = compact('iv', 'dataSize', 'encryptedData');
    unset($encryptedData);
    $serialized = serialize($struct);
    unset($struct);
    $pack = base64_encode($serialized);
    unset($serialized);
    return $pack;
}

function decrypt($pack, $key = null)
{
    if (is_null($key)) {
        if (!defined('CRYPT_KEY')) throw new Exception("CRYPT_KEY constant didn't defined. Probable this app was not installed properly.");
        else $key = CRYPT_KEY;
    }
    $serialized = base64_decode($pack);
    unset($pack);
    $struct = unserialize($serialized);
    unset($serialized);
    $data = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $struct['encryptedData'], MCRYPT_MODE_CBC, $struct['iv']);
    unset($struct['encryptedData']);
    $result = substr($data, 0, $struct['dataSize']);
    unset($data);
    return $result;
}

function readln($prompt = '')
{
    if ($prompt !== '') {
        out($prompt);
    }
    $result = fgets(STDIN);
    return $result;
}

function isHttps()
{
    if (empty($_SERVER['HTTPS'])) return false;
    if ($_SERVER['HTTPS'] !== 'on') return false;
    return true;
}

function formatLink($uri)
{
    $result = isHttps() ? 'https://' : 'http://';
    $result .= SITE_HOST;
    $result .= '/' . ltrim($uri, '/');
    return $result;
}

/**
 * @return Session
 */
function getSession()
{
    static $session = null;
    if (!($session instanceof Session)) {
        $session = new Session();
    }
    return $session;
}

function getHeader($headerName)
{
    static $headers = null;
    if (!is_array($headers)) {
        $alias = [
            'HTTP_USER_AGENT' => 'user-agent',
            'HTTP_REFERER' => 'referer',
            'HTTP_HOST' => 'host',
            'HTTP_CONNECTION' => 'connection',
            'HTTP_ACCEPT_LANGUAGE' => 'accept-language',
            'HTTP_ACCEPT_ENCODING' => 'accept-encoding',
            'HTTP_ACCEPT_CHARSET' => 'accept-charset',
            'HTTP_ACCEPT' => 'accept',
        ];
        $headers = [];
        foreach ($alias as $alias => $name) {
            if (isset($_SERVER[$alias])) {
                $headers[$name] = $_SERVER[$alias];
            }
        }
    }
    $headerName = trim(strtolower($headerName));
    return isset($headers[$headerName]) ? $headers[$headerName] : null;
}

function dattimParse($dattim)
{
    $regexpr = '/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2}|[0-9]{4})(\s([0-9]{1,2})\:([0-9]{1,2})(\:([0-9]{1,2}))?)?$/';
    if (!preg_match($regexpr, trim($dattim), $matches)) {
        return null;
    }
    $day = intval($matches[1]);
    $month = intval($matches[2]);
    $year = intval($matches[3]);
    $hour = isset($matches[5]) ? intval($matches[5]) : 0;
    $minute = isset($matches[6]) ? intval($matches[6]) : 0;
    $second = isset($matches[8]) ? intval($matches[8]) : 0;
    if ($day > 31) return null;
    if ($month > 12) return null;
    //if ($year > 2038) return null;
    if ($hour > 23) return null;
    if ($minute > 59) return null;
    if ($second > 59) return null;
    $result = mktime($hour, $minute, $second, $month, $day, $year);
    return $result;
}

function isBinary(&$data)
{
    $regexp = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/';
    return preg_match($regexp, $data) === 1;
}

function eolNormalize($html)
{
    if (is_int(strpos($html, "\r\n"))) {
        $result = str_replace("\r\n", "\n", $html);
        return $result;
    } else {
        return $html;
    }
}

function eol2br($html)
{
    $result = eolNormalize($html);
    $result = str_replace("\n", "<br>\n", $result);
    return $result;
}

function selectNumneral($number, array $numerals)
{
    if (!is_array($numerals) || !isset($numerals[0]) || !isset($numerals[1]) || !isset($numerals[2])) {
        throw new \InvalidArgumentException('Incorrect value of $numerals argument');
    }
    $num = intval($number);
    if ($num < 0) $num = -$num;
    if ($num === 0) {
        return $numerals[0];
    }
    if ($num === 1) {
        return $numerals[1];
    }
    if ($num >= 2 && $num <= 4) {
        return $numerals[2];
    }
    if ($num >= 5 && $num <= 19) {
        return $numerals[0];
    }
    $num = intval(substr($num, strlen($num) - 1));
    if ($num === 0) {
        return $numerals[0];
    }
    if ($num === 1) {
        return $numerals[1];
    }
    if ($num >= 2 && $num <= 4) {
        return $numerals[2];
    }
    return $numerals[0];
}

/**
 * @param string $url
 * @param array $curlOptions
 * @throws Exception
 * @return string
 */
function httpGet($url, array $curlOptions = [])
{
    $ch = curl_init($url);
    if (!isset($curlOptions[CURLOPT_TIMEOUT])) $curlOptions[CURLOPT_TIMEOUT] = 10;
    if (!isset($curlOptions[CURLOPT_FOLLOWLOCATION])) $curlOptions[CURLOPT_FOLLOWLOCATION] = true;
    $curlOptions[CURLOPT_RETURNTRANSFER] = true;
    curl_setopt_array($ch, $curlOptions);
    $result = strval(curl_exec($ch));
    if ($result !== '') return $result;
    $errno = curl_errno($ch);
    if ($errno === 0) return $result;
    $errstr = curl_error($ch);
    throw new Exception('cURL request failed: ' . $errstr);
}

function tmpCleanup($dir, $timeout = SEC_DAY)
{
    static $depth = 0;
    static $now = null;
    if (is_null($now)) {
        $now = time();
    }
    if ($depth > 100) {
        throw new Exception('Stack overflow');
    }
    $fsNodes = scandir($dir);
    $fsNodesCount = count($fsNodes);
    $deletesCount = 0;
    foreach ($fsNodes as $fsNode) {
        if ($fsNode === '.' || $fsNode === '..') continue;
        $fsNodePath = $dir . '/' . $fsNode;
        if (is_dir($fsNodePath)) {
            $depth++;
            $dirEmpty = tmpCleanup($fsNodePath);
            $depth--;
            if ($dirEmpty && @rmdir($fsNodePath)) {
                $deletesCount++;
            }
        } elseif (is_file($fsNodePath)) {
            $mtime = filemtime($fsNodePath);
            if (($now - $mtime) > $timeout) {
                if (@unlink($fsNodePath)) $deletesCount++;
            }
        } else {
            errorLog('Unknown filesystem node type: ' . $fsNodePath);
        }
    }
    if ($deletesCount >= ($fsNodesCount-2)) {
        return true;
    } else {
        return false;
    }
}

function listOptions(array $options, $selected = null)
{
    $result = '';
    if (!is_null($selected)) {
        $selected = strval($selected);
    }
    foreach ($options as $value => $title) {
        $value = strval($value);
        $result .= "<option value='" . escapeHTML($value) . "'";
        if ($value === $selected) $result .= ' selected';
        $result .= '>' . escapeHTML($title) . '</option>';
    }
    return $result;
}

function shellExecute($command, &$stdout, &$stderr)
{
    $stdout = '';
    $stderr = '';
    $descriptorspec = [
        1 => array('pipe', 'w'), // stdout
        2 => array('pipe', 'w')  // stderr
    ];
    $proc = proc_open($command, $descriptorspec, $pipes);
    if (!is_resource($proc)) {
        throw new Exception("Failed to create process by command: $command");
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    proc_close($proc);
}

function processList()
{
    if (preg_match('/^win/i', PHP_OS)) {
        $cmd = 'tasklist /v /fo csv';
        shellExecute($cmd, $stdout, $stderr);
        $lines = explode("\n", $stdout);
        $cnt = count($lines);
        if ($cnt < 3) {
            throw new Exception("Failed to retrieve process list from `$cmd` command (1)");
        }
        $result = [];
        for ($j=1; $j<$cnt; $j++) {
            $line = trim($lines[$j]);
            if ($line === '') continue;
            $cols = str_getcsv($line);
            if (count($cols) <> 9) continue;
            $pid = intval($cols[1]);
            $row = [
                'command' => $cols[0],
                'mem' => intval(preg_replace('/[^0-9]/', '', $cols[4])),
                'user' => $cols[6],
                'title' => $cols[8]
            ];
            $result[$pid] = $row;
        }
        if (count($result) < 2) {
            throw new Exception("Failed to retrieve process list from `$cmd` command (2)");
        }
        return $result;
    } else {
        $result = [];
        shellExecute('/bin/ps auxh', $stdout, $stderr);
        $lines = explode("\n", $stdout);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cols = preg_split('/\s+/', $line, 11);
            if (count($cols) !== 11) {
                throw new Exception("Wrong columns count at ps output line: $line");
            }
            $result[] = array(
                'user' => $cols[0],
                'pid' => intval($cols[1]),
                'cpu' => floatval($cols[2]),
                'mem' => floatval($cols[3]),
                'vsz' => intval($cols[4]),
                'rss' => intval($cols[5]),
                'tty' => $cols[6],
                'stat' => $cols[7],
                'start' => $cols[8],
                'time' => $cols[9],
                'command' => $cols[10]
            );
        }
        if (count($result) < 1) {
            throw new Exception('Failed to get process list');
        }
        return $result;
    }
}