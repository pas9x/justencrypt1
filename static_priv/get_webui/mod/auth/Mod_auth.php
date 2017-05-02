<?php

class Mod_auth extends WebuiModule {
    public function getName()
    {
        return 'auth';
    }

    public function getTitle()
    {
        return 'Авторизация';
    }

    public function func_index()
    {
        $tpl = new Template(PRIVDIR . '/templates/webui');
        $tpl->display('auth');
    }

    public function func_doit()
    {
        if (!checkPost('pass')) {
            displayError('Требуется POST-параметр pass');
        }
        $pass = post('pass');

        if ($pass === '') {
            displayError('Вы не указали пароль');
        }
        $passHash = sha1priv($pass);

        $authInterval = getOption('authInterval');
        $lastAuthTry = getOption('lastAuthTry', 0);
        $now = time();
        $delta = $now - $lastAuthTry;
        if ($delta < $authInterval) {
            displayError('Предыдущая попытка авторизации была совсем недавно. Подождите несколько секунд и повторите попытку. Слишком частые попытки входа не допускаются.');
        }
        setOption('lastAuthTry', $now);
        if (getOption('adminPass') !== $passHash) {
            displayError('Неверный пароль. Указанный вами пароль не равен паролю администратора.');
        }
        $sessid = keygen(32);
        setcookie('sessid', $sessid, $now + SEC_YEAR, null, null, null, true);
        setOption('adminSessid', $sessid);
        setOption('adminIP', $_SERVER['REMOTE_ADDR']);
        setOption('adminSessionExpire', $now + getOption('sessionLifetime'));

        $link = self::modLink('index');
        redirect($link);
    }

    public function func_logout()
    {
        $backLink = $this->selfLink();
        if (isAdmin()) {
            delOption('adminSessid');
            delOption('adminIP');
            displayOk('Сессия завершена, авторизация снята.', $backLink, 'Авторизация');
        } else {
            displayError('Вы итак не авторизированы.', $backLink, 'Авторизация');
        }
    }
}