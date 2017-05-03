<?php

use \app\Exporter;
use \phpseclib\Net\SCP;

class Exporter_apache extends Exporter
{
    public static function getName()
    {
        return 'apache';
    }

    public static function getTitle()
    {
        return 'Apache';
    }

    public static function exampleFinalCommand()
    {
        return 'service httpd reload';
    }

    public static function optionsFromUserForm()
    {
        $errors = [];
        $options = [];

        $options['privateKeyFile'] = param('privateKeyFile', '');
        if ($options['privateKeyFile'] === '') {
            $errors[] = 'Не указан путь к приватному ключу сертификата';
        }

        $options['certFile'] = param('certFile', '');
        if ($options['certFile'] === '') {
            $errors[] = 'Не указан путь к файлу сертификата';
        }

        $options['chainFile'] = param('chainFile', '');
        if ($options['chainFile'] === '') {
            $errors[] = 'Не указан путь к файлу цепочки сертификатов';
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
        $cnt = count($chain);
        unset($chain[$cnt-1]);
        $chainText = implode("\n\n", $chain);
        return $chainText;
    }

    protected function exportFiles()
    {
        $privateKey = $this->cert['privateKey'];
        $certText = $this->cert['certDomain'];
        $chainText = $this->formatChain();
        $ssh = $this->getSsh();
        $scp = new SCP($ssh);

        $ok = $scp->put($this->options['privateKeyFile'], $privateKey, SCP::SOURCE_STRING);
        if (!$ok) {
            throw new Exception('Failed to write private key file ' . $this->options['privateKeyFile']);
        }

        $ok = $scp->put($this->options['certFile'], $certText, SCP::SOURCE_STRING);
        if (!$ok) {
            throw new Exception('Failed to write certificate file ' . $this->options['certFile']);
        }

        $ok = $scp->put($this->options['chainFile'], $chainText, SCP::SOURCE_STRING);
        if (!$ok) {
            throw new Exception('Failed to write certificate file ' . $this->options['chainFile']);
        }

        return $ssh;
    }
}