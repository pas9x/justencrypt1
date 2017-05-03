<?php

class Mod_config extends WebuiModuleAdmin {
    public function getName() {
        return 'config';
    }

    public function getTitle() {
        return 'Настройки системы';
    }

    public function func_index() {
        $this->template = 'config';
        $this->title = 'Настройки системы';
        $this->signs = [
            'action' => $this->selfLink('saveConfig'),
            'leRegisterLink' => self::modLink('cert', 'registerAccountConfirm'),
            'authInterval' => getOption('authInterval'),
            'prolongDaysEarly' => getOption('prolongDaysEarly'),
            'sessionLifetime' => getOption('sessionLifetime'),
            'adminEmail' => getOption('adminEmail'),
            'defaultCsrTemplate' => getOption('defaultCsrTemplate'),
            'leAccountID' => getOption('leAccountID', ''),
            'leAccountKey' => getOption('leAccountKey', ''),
        ];
        $this->display();
    }

    function func_saveConfig() {
        $errors = [];

        $sessionLifetime = intval(post('sessionLifetime'));
        if ($sessionLifetime < 60) {
            $errors[] = 'Время жизни сессии не должно быть меньше 60 секунд';
        }

        $authInterval = intval(post('authInterval'));
        if ($authInterval < 1) {
            $errors[] = 'Интервал между попытками входа не должен быть меньше 1 секунды';
        }

        $prolongDaysEarly = intval(post('prolongDaysEarly'));
        if ($prolongDaysEarly < 1) {
            $errors[] = 'Неверно указано количество дней до продления сертификата';
        } elseif ($prolongDaysEarly > 30) {
            $errors[] = 'Продление сертификата допускается не раньше чем за 30 дней до окончания его срока действия';
        }

        $newPass = post('newPass');
        if ($newPass !== '') {
            if (!checkLength($newPass, 8, 32)) $errors[] = 'Новый пароль должен иметь длину от 8 до 32 символов. Не нужно заполнять это поле если вы не хотите менять пароль.';
            else $newPass = sha1($newPass);
        }

        $leAccountID = post('leAccountID');
        if ($leAccountID !== '') {
            $leAccountID = intval($leAccountID);
            if ($leAccountID < 1) $errors[] = 'ID аккаунта Let`s Encrypt не должен быть меньше 1';
        }

        $adminEmail = post('adminEmail');
        if ($adminEmail === '') {
            $errors[] = 'Вы не указали e-mail администратора';
        } elseif (!StringValidator::check('email', $adminEmail)) {
            $errors[] = 'E-mail администратора указан неверно';
        }

        $leAccountKey = trim(post('leAccountKey'));

        $nameRegexpr = '/^[a-z0-9][a-z0-9\- ]{0,48}[a-z0-9]$/i';
        $defaultCsrTemplate = [
            'countryName' => strtoupper(post('countryName')),
            'stateOrProvinceName' => post('stateOrProvinceName'),
            'localityName' => post('localityName'),
            'organizationName' => post('organizationName'),
            'organizationalUnitName' => post('organizationalUnitName')
        ];
        if (!preg_match('/^[A-Z]{2}$/', $defaultCsrTemplate['countryName'])) {
            $errors[] = 'Неверно указан код страны. Код состоит из двух латинских букв в верхнем регистре.';
        }
        if (!preg_match($nameRegexpr, $defaultCsrTemplate['stateOrProvinceName'])) {
            $errors[] = 'Область/Штат указаны неверно. Длина: от 2 до 50 символов. Допускаются только буквы латинского алфавита, цифры, пробелы и дефисы.';
        }
        if (!preg_match($nameRegexpr, $defaultCsrTemplate['localityName'])) {
            $errors[] = 'Город указан неверно. Длина: от 2 до 50 символов. Допускаются только буквы латинского алфавита, цифры, пробелы и дефисы.';
        }
        if (!preg_match($nameRegexpr, $defaultCsrTemplate['organizationName'])) {
            $errors[] = 'Название организации указано неверно. Длина: от 2 до 50 символов. Допускаются только буквы латинского алфавита, цифры, пробелы и дефисы.';
        }
        if (!preg_match($nameRegexpr, $defaultCsrTemplate['organizationalUnitName'])) {
            $errors[] = 'Отдел организации указан неверно. Длина: от 2 до 50 символов. Допускаются только буквы латинского алфавита, цифры, пробелы и дефисы.';
        }

        if ($errors) {
            displayError($errors);
        }

        setOption('sessionLifetime', $sessionLifetime);
        setOption('authInterval', $authInterval);
        setOption('prolongDaysEarly', $prolongDaysEarly);
        if ($newPass !== '') {
            setOption('adminPass', $newPass);
        }
        if ($leAccountID !== '') {
            setOption('leAccountID', $leAccountID);
        } else {
            delOption('leAccountID');
        }
        setOption('adminEmail', $adminEmail);
        setOption('leAccountKey', $leAccountKey);
        setOption('defaultCsrTemplate', $defaultCsrTemplate);

        $backLink = $this->selfLink();
        displayOK('Настройки панели сохранены', $backLink, 'Вернуться к настройкам');
    }
}