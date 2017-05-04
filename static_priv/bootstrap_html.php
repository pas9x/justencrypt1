<?php

function htmlErrorPrinter($message)
{
    $debug = defined('DEBUG') && DEBUG;
    $clean = outputClean();
    $message = escapeHTML($message);
    $errorPageTemplate = file_get_contents(PRIVDIR . '/error_page.html');
    $errorCrashTemplate = file_get_contents(PRIVDIR . '/error_crash.html');
    if ($clean) {
        http_response_code(500);
        if ($debug) {
            $page = str_replace('{message}', $message, $errorPageTemplate);
            die($page);
        } else {
            $message = 'Произошла непредвиденная ошибка. Информация о проблеме сохранена в лог ошибок.';
            $page = errhtmlGenerate($message);
        }
        out($page);
    } else {
        if (!$debug) {
            $message = 'Произошла непредвиденная ошибка';
        }
        $dying = str_replace('{message}', $message, $errorCrashTemplate);
        out($dying);
    }
};

function okhtmlGenerate($message, $backLink = null, $backLabel = null, $html = false)
{
    if (!is_scalar($message)) {
        throw new InvalidArgumentException('Invalid type of $message: ' . gettype($message));
    }
    $message = strval($message);
    if (!is_null($backLink) || !is_null($backLabel)) {
        if (!is_string($backLink) || !is_string($backLabel)) {
            throw new InvalidArgumentException('$backLabel and $backLing both should be an string or null');
        }
    } else {
        $backLink = empty($_SERVER['HTTP_REFERER']) ? '' : escapeHTML($_SERVER['HTTP_REFERER']);
    }
    $template = new Template(PRIVDIR . '/templates/webui');
    $signs = compact('message', 'backLink', 'backLabel', 'html');
    $result = $template->render('okmsg', $signs);
    return $result;
}

function errhtmlGenerate($messages, $backLink = null, $backLabel = null, $html = false)
{
    if (is_scalar($messages)) {
        $messages = array(strval($messages));
    }
    elseif (is_array($messages)) {
        $messages = array_values($messages);
    }
    else {
        throw new InvalidArgumentException('Invalid type of $messages argument: ' . gettype($messages));
    }
    if (!is_null($backLink) || !is_null($backLabel)) {
        if (!is_string($backLink) || !is_string($backLabel)) {
            throw new InvalidArgumentException('$backLabel and $backLing both should be an string or null');
        }
    } else {
        $backLink = empty($_SERVER['HTTP_REFERER']) ? '' : escapeHTML($_SERVER['HTTP_REFERER']);
    }
    $template = new Template(PRIVDIR . '/templates/webui');
    $signs = compact('messages', 'backLink', 'backLabel', 'html');
    $result = $template->render('errmsg', $signs);
    return $result;
}

function displayOK($message, $backLink = null, $backLabel = null, $html = false)
{
    $html = okhtmlGenerate($message, $backLink, $backLabel, $html);
    if (!outputClean()) {
        throw new Exception('Cannot display OK-message because headers already sent');
    }
    stop($html);
}

function displayError($messages, $backLink = null, $backLabel = null, $html = false)
{
    $html = errhtmlGenerate($messages, $backLink, $backLabel, $html);
    if (!outputClean()) {
        throw new Exception('Cannot display error-message because headers already sent');
    }
    stop($html);
}

function error404()
{
    $template = new Template(PRIVDIR . '/templates/webui');
    if (empty($_SERVER['HTTP_REFERER'])) {
        $backLink = '';
        $backLabel = '';
    } else {
        $backLink = $_SERVER['HTTP_REFERER'];
        $backLabel = 'Назад';
    }
    $signs = compact('backLink', 'backLabel');
    http_response_code(404);
    $template->display('error404', $signs);
}

header('Content-Type: text/html; charset=utf-8');
errorPrinter('htmlErrorPrinter');
