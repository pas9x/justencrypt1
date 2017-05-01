<?php

abstract class WebuiModuleAdmin extends WebuiModule {
    protected $template = null;
    protected $title = null;
    protected $signs = [];
    protected $templateDirectories = [];

    public function __construct() {
        if (!isAdmin()) {
            $backLink = self::modLink('auth');
            displayError('Доступ к этому модулю разрешён только авторизированным администраторам', $backLink, 'Авторизация');
        }
        parent::__construct();
    }

    public function display() {
        if (is_null($this->template)) {
            throw new Exception('No template to render');
        }
        $signs = $this->signs;
        if (!is_null($this->title)) {
            if (isset($signs['title'])) {
                throw new Exception("You must not use 'title' property with 'title' sign together");
            } else {
                $signs['title'] = $this->title;
            }
        }
        $templateDirectories = [
            $this->getDirectory() . '/templates',
            PRIVDIR . '/templates/webui'
        ];
        $templateDirectories = array_merge($templateDirectories, $this->templateDirectories);
        $tpl = new Template($templateDirectories);
        $tpl->display($this->template, $signs);
    }
}