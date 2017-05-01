<?php

use \app\SSH;

class Mod_ssh extends WebuiModuleAdmin {
    public function getName() {
        return 'ssh';
    }

    public function getTitle() {
        return 'SSH-аккаунты';
    }

    public function func_index() {
        $this->template = 'ssh';
        $this->title = 'SSH-аккаунты';
        $pg = param('pg', 1);
        $pp = param('pp', 25);
        $accounts = SSH::listAccounts($pg, $pp);
        $this->signs['accounts'] = [];
        foreach ($accounts as $account) {
            $account['displayHost'] = $account['host'];
            if ($account['port'] !== 22) $account['displayHost'] .= ':' . $account['port'];
            $account['editLink'] = $this->selfLink('editAccount', ['idSsh'=>$account['idSsh']]);
            $account['delLink'] = $this->selfLink('deleteAccount', ['idSsh'=>$account['idSsh']]);
            $this->signs['accounts'][] = $account;
        }
        $this->display();
    }

    public function func_newAccountForm() {
        $this->template = 'newAccountForm';
        $this->title = 'Новый SSH-аккаунт';
        $this->signs['action'] = $this->selfLink('addAccount');
        $this->display();
    }

    public function func_addAccount() {
        $sharedName = post('sharedName');
        $host = post('host');
        $port = post('port');
        $login = post('login');
        $authType = intval(post('authType'));

        if ($authType === SSH::ACCESS_TYPE_PASSWORD) {
            $authValue = post('pass');
        } elseif ($authType === SSH::ACCESS_TYPE_KEY) {
            $authValue = post('key');
        } else {
            throw new Exception('Invalid value of $authType: ' . $authType);
        }
        SSH::addAccount(true, $sharedName, $host, $port, $login, $authType, $authValue);
        $backLink = $this->selfLink();
        displayOK('SSH аккаунт добавлен', $backLink, 'Список аккаунтов');
    }

    public function func_editAccount() {
        $idSsh = get('idSsh');
        $account = SSH::getAccount($idSsh);
        if (!$account['shared']) {
            displayError('Это специфический аккаунт привязанный к SSL-сертификату. Редактировать его следует в настройках сертификата.');
        }
        $this->template = 'editAccount';
        $this->title = 'Настройки ssh-аккаунта';
        $this->signs['action'] = $this->selfLink('saveAccount');
        $this->signs += $account;
        $this->display();
    }

    public function func_saveAccount() {
        $idSsh = post('idSsh');
        $account = SSH::getAccount($idSsh);

        $authType = intval(post('authType'));
        if ($authType === SSH::ACCESS_TYPE_PASSWORD) {
            $authValue = post('pass');
        } elseif ($authType === SSH::ACCESS_TYPE_KEY) {
            $authValue = post('key');
        } else {
            throw new Exception('Invalid value of $authType:' . $authType);
        }

        if ($authValue === '') {
            if ($authType !== $account['authType']) {
                displayError('При смене типа ssh-авторизации нужно указать новый пароль/ключ');
            }
            $authValue = $account['authValue'];
        }

        SSH::saveAccount(
            post('idSsh'),
            true,
            post('sharedName'),
            post('host'),
            post('port'),
            post('login'),
            $authType,
            $authValue
        );

        $backLink = $this->selfLink();
        displayOK('Настройки SSH-аккаунта сохранены', $backLink, 'Список аккаунтов');
    }

    public function func_deleteAccount() {
        SSH::deleteAccount(get('idSsh'));
        $backLink = $this->selfLink();
        displayOK('SSH-аккаунт удалён', $backLink, 'Список аккаунтов');
    }
}