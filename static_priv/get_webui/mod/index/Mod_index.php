<?php

class Mod_index extends WebuiModule {
    public function getName() {
        return 'index';
    }

    public function getTitle() {
        return 'Главная';
    }

    public function func_index() {
        $url = self::modLink(isAdmin() ? 'dashboard' : 'auth');
        redirect($url);
    }
}