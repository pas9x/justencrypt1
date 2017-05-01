<?php

use \app\Exporter;
use \phpseclib\Net\SCP;

class Exporter_nginx extends Exporter
{
    public static function getName()
    {
        return 'nginx';
    }

    public static function getTitle()
    {
        return 'Nginx';
    }

    public static function exampleFinalCommand()
    {
        return 'service nginx reload';
    }

    public static function optionsFromUserForm()
    {
        $errors = [];
        $options = [];

        $options['privateKeyFile'] = param('privateKeyFile', '');
        if ($options['privateKeyFile'] === '') {
            $errors[] = 'Не указан путь к приватному ключу сертификата';
        }

        $options['chainFile'] = param('chainFile', '');
        if ($options['chainFile'] === '') {
            $errors[] = 'Не указан путь к файлу к цепочки сертификатов';
        }

        if ($errors) {
            throw new ErrorMessage($errors);
        } else {
            return $options;
        }
    }

    protected function formatChain()
    {
        $chain = $this->getChain(true);
        $chainText = implode("\n\n", $chain);
        return $chainText;
    }

    protected function exportFiles()
    {
        $chainText = $this->formatChain();
        $privateKey = $this->cert['privateKey'];
        $ssh = $this->getSsh();
        $scp = new SCP($ssh);
        $ok = $scp->put($this->options['privateKeyFile'], $privateKey, SCP::SOURCE_STRING);
        if (!$ok) {
            throw new Exception('Failed to write private key to ' . $this->options['privateKeyFile']);
        }
        $ok = $scp->put($this->options['chainFile'], $chainText, SCP::SOURCE_STRING);
        if (!$ok) {
            throw new Exception('Failed to write certificate to ' . $this->options['chainFile']);
        }
        return $ssh;
    }
}