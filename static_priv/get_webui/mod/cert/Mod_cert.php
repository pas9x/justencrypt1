<?php

use \pas9x\letsencrypt\LetsEncrypt;
use \app\SSH;
use \app\Cert;

class Mod_cert extends WebuiModuleAdmin {
    public function getName() {
        return 'cert';
    }

    public function getTitle() {
        return 'SSL-сертификаты';
    }

    public function func_index() {
        $certificates = Cert::listCert();
        $now = time();
        foreach ($certificates as $num => $cert) {
            $idCert = $cert['idCert'];
            $cert['delLink'] = $this->selfLink('deleteCert', compact('idCert'));
            $cert['showLink'] = $this->selfLink('showCert', compact('idCert'));
            $daysDue = intval(($cert['expireTimestamp'] - $now) / SEC_DAY);
            if ($daysDue > 3) $cert['color'] = '';
            elseif ($daysDue > 0) $cert['color'] = 'colorWarn';
            else $cert['color'] = 'colorBad';
            $certificates[$num] = $cert;
        }
        $this->template = 'cert';
        $this->title = 'Список сертификатов';
        $this->signs['certificates'] = $certificates;
        $this->display();
    }

    public function func_showCert()
    {
        $idCert = intval(get('idCert'));
        $cert = Cert::getCert($idCert);
        $this->template = 'showCert';
        $this->title = 'Параметры сертификата';
        $this->signs += $cert;
        $this->signs['exportsLink'] = self::modLink('exports', 'certExports', compact('idCert'));
        $this->signs['editCertLink'] = $this->selfLink('editCert', compact('idCert'));
        $this->signs['testVerifyLink'] = $this->selfLink('testVerify', compact('idCert'));
        $this->signs['revokeLink'] = $this->selfLink('revokeCert', compact('idCert'));
        $this->signs['reissueLink'] = $this->selfLink('reissueCert', compact('idCert'));
        $this->signs['deleteLink'] = $this->selfLink('deleteCert', compact('idCert'));
        $this->display();
    }

    public function func_registerAccountConfirm() {
        $leAccountKey = getOption('leAccountKey', '');
        if ($leAccountKey !== '') {
            $msg = "У вас уже есть аккаунт Let`s Encrypt! Не надо регистрировать кучу аккаунтов, сервер Let`s Encrypt не резиновый.\n";
            $msg .= "Если обойтись без регистрации нового аккаунта нельзя: перейдите в ";
            $msg .= "<a href='" . self::modLink('config') . "'>настройки системы</a>";
            $msg .= ", очистите поле &quot;приватный ключ&quot; и повторите регистрацию.";
            displayError($msg, null, null, true);
        }
        $this->template = 'registerAccountConfirm';
        $this->title = 'Регистрация аккаунта Let`s Encrypt';
        $this->signs['confirmLink'] = $this->selfLink('registerAccount');
        $this->signs['adminEmail'] = getOption('adminEmail');
        $this->signs['configLink'] = self::modLink('config');
        $this->display();
    }

    public function func_registerAccount() {
        $leAccountKey = LetsEncrypt::generatePrivateKey(true);
        $contacts = ['mailto:' . getOption('adminEmail')];
        $le = new LetsEncrypt($leAccountKey);
        try {
            $leAccountID = $le->registerAccount($contacts);
        } catch (Exception $e) {
            $msg = 'Зарегистрировать аккаунт не удалось из-за возникшей ошибки: ' . $e->getMessage();
            displayError($msg);
        }
        setOption('leAccountID', $leAccountID);
        setOption('leAccountKey', $leAccountKey);
        $backLink = self::modLink('config');
        displayOK('Аккаунт Let`s Encrypt успешно зарегистрирован.', $backLink, 'Просмотреть детали');
    }

    public function func_newCertForm() {
        $leAccountKey = getOption('leAccountKey', '');
        if (empty($leAccountKey)) {
            $backLink = $this->selfLink('registerAccountConfirm');
            $msg = 'В настройках системы не указан приватный ключ аккаунта. Возможно вы ещё не зарегистрировали аккаунт Let`s Encrypt.';
            displayError($msg, $backLink, 'Зарегистрировать аккаунт');
        }
        $this->template = 'newCertForm';
        $this->title = 'Регистрация SSL-сертификата';
        $this->signs['sshAccounts'] = SSH::listAccounts();
        $this->signs['action'] = $this->selfLink('registerCert');
        $this->display();
    }

    public function func_registerCert() {
        $shared = intval(post('shared'));
        if ($shared) {
            $idSsh = intval(post('idSsh'));
        } else {
            $host = post('host');
            $port = intval(post('port'));
            $login = post('login');
            $authType = intval(post('authType'));
            if ($authType === SSH::ACCESS_TYPE_PASSWORD) $authValue = post('pass');
            elseif ($authType === SSH::ACCESS_TYPE_KEY) $authValue = post('key');
            else displayError('Недопустимое значение параметра authType');
            try {
                $idSsh = SSH::addAccount(false, '', $host, $port, $login, $authType, $authValue);
            } catch (ErrorMessage $e) {
                displayError($e->getMessages());
            } catch (Exception $e) {
                $msg = 'Проверка указанного вами SSH-аккаунта завершилась ошибкой: ' . $e->getMessage();
                displayError($msg);
            }
        }

        try {
            $idCert = Cert::registerCert(post('domain'), post('documentRoot'), $idSsh);
        } catch (Exception $e) {
            if (!$shared) SSH::deleteAccount($idSsh);
            if ($e instanceof ErrorMessage) displayError($e->getMessages());
            displayError('Сертификат небыл зарегистрирован из-за возникшей ошибки: ' . $e->getMessage());
        }

        $backLink = $this->selfLink();
        displayError('Сертификат успешно зарегистрирован', $backLink, 'Список сертификатов');
    }

    public function func_editCert()
    {
        $idCert = intval(get('idCert'));
        $cert = Cert::getCert($idCert);
        $this->signs['cert'] = $cert;
        $this->signs['ssh'] = SSH::getAccount($cert['idSsh']);
        $this->signs['sshAccounts'] = SSH::listAccounts();
        $this->signs['action'] = $this->selfLink('saveCert');
        $this->template = 'editCert';
        $this->title = 'Настройка верификации';
        $this->display();
    }

    public function func_saveCert()
    {
        $idCert = intval(post('idCert'));
        $cert = Cert::getCert($idCert);
        $sshAccountCurrent = SSH::getAccount($cert['idSsh']);

        $shared = intval(post('shared'));
        if ($shared) {
            $idSsh = intval(post('idSsh'));
            if (!$sshAccountCurrent['shared']) $idSshDeleteSuccess = $sshAccountCurrent['idSsh'];
        } else {
            $host = post('host');
            $port = intval(post('port'));
            $login = post('login');
            $authType = intval(post('authType'));
            if ($authType === SSH::ACCESS_TYPE_PASSWORD) $authValue = post('pass');
            elseif ($authType === SSH::ACCESS_TYPE_KEY) $authValue = post('key');
            else displayError('Недопустимое значение параметра authType');
            if ($authValue === '') {
                if ($sshAccountCurrent['shared']) displayError('При смене аккаунта с шаблонного на отдельный нужно указать пароль/ключ для подключения к SSH-серверу');
                $authValue = $sshAccountCurrent['authValue'];
            }
            try {
                if (!$sshAccountCurrent['shared']) $idSshDeleteSuccess = $sshAccountCurrent['idSsh'];
                $idSsh = $idSshDeleteFail = SSH::addAccount(false, '', $host, $port, $login, $authType, $authValue);
            } catch (ErrorMessage $e) {
                displayError($e->getMessages());
            } catch (Exception $e) {
                $msg = 'Проверка указанного вами SSH-аккаунта завершилась ошибкой: ' . $e->getMessage();
                displayError($msg);
            }
        }

        try {
            Cert::saveCert($idCert, $idSsh, post('documentRoot'));
        } catch (Exception $e) {
            if (isset($idSshDeleteFail)) SSH::deleteAccount($idSshDeleteFail);
            displayError('Настройки сертификата не сохранены: ' . $e->getMessage());
        }

        if (isset($idSshDeleteSuccess)) {
            SSH::deleteAccount($idSshDeleteSuccess);
        }
        $backLink = $this->selfLink('showCert', compact('idCert'));
        displayOK('Настройки сертификата успешно сохранены', $backLink, 'К сертификату');
    }

    public function func_testVerify()
    {
        $idCert = intval(get('idCert'));
        try {
            Cert::testVerify($idCert);
        } catch (Exception $e) {
            displayError('Попытка верификации домена завершилась ошибкой: ' . $e->getMessage());
        }
        $backLink = $this->selfLink('showCert', compact('idCert'));
        displayOK('Верификация домена работает нормально', $backLink, 'К сертификату');
    }

    public function func_revokeCert()
    {
        $idCert = intval(get('idCert'));
        try {
            Cert::revokeCert($idCert);
        } catch (Exception $e) {
            displayError('Отозвать сертификат не удалось: ' . $e->getMessage());
        }
        $backLink = $this->selfLink('showCert', compact('idCert'));
        displayOK('Операция отзыва сертификата выполнена успешно', $backLink, 'К сертификату');
    }

    public function func_deleteCert()
    {
        $idCert = intval(get('idCert'));
        try {
            Cert::deleteCert($idCert);
        } catch (Exception $e) {
            displayError('Удалить сертификат не удалось: ' . $e->getMessage());
        }
        $backLink = $this->selfLink();
        displayOK('Операция отзыва сертификата выполнена успешно', $backLink, 'Список сертификатов');
    }

    public function func_reissueCert()
    {
        $idCert = intval(get('idCert'));
        try {
            Cert::revokeCert($idCert);
        } catch (Exception $e) {
        }
        try {
            Cert::reissueCert($idCert);
        } catch (Exception $e) {
            displayError('Не удалось перевыпустить сертификат: ' . $e->getMessage());
        }
        $backLink = $this->selfLink('showCert', compact('idCert'));
        displayOK('Сертификат перевыпущен успешно', $backLink, 'К сертификату');
    }
}