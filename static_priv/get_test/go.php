<?php

header('Content-Type: text/plain; charset=windows-1251');

if (file_exists(__DIR__ . '/dev.php')) {
    include __DIR__ . '/dev.php';
    exit;
}

echo "Only for development purposes.\n";
