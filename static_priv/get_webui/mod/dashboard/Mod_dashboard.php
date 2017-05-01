<?php

class Mod_dashboard extends WebuiModuleAdmin {
    public function getName() {
        return 'dashboard';
    }

    public function getTitle() {
        return 'Главная';
    }

    public function func_index() {
        $errorLog = DATADIR . '/error.log';
        $this->signs['errorLog'] = file_exists($errorLog) && (filesize($errorLog) > 0);
        $this->template = 'dashboard';
        $this->title = 'Главная';
        $this->display();
    }
}