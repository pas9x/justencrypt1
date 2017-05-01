<?php

function installed() {
    $dbFile = DATADIR . '/db.sqlite';
    if (!file_exists($dbFile)) {
        return false;
    }
    $size = filesize($dbFile);
    if ($size < 1) {
        return false;
    }
    return true;
}

function isAdmin() {
    if (!isset($_COOKIE['sessid'])) return false;
    $sessid = $_COOKIE['sessid'];
    if (!preg_match('/^[a-z0-9]{32}$/i', $sessid)) return false;
    if ($sessid !== getOption('adminSessid', null)) return false;
    if ($_SERVER['REMOTE_ADDR'] !== getOption('adminIP', null)) return false;
    $expire = getOption('adminSessionExpire', 0);
    $lifetime = getOption('sessionLifetime');
    $now = time();
    if ($expire < $now) return false;
    if (($expire - ($lifetime - 60)) < $now) {
        setOption('adminSessionExpire', $now + $lifetime);
    }
    return true;
}
