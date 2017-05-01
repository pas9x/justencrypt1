<?php

//$pkey = LetsEncrypt::generatePrivateKey(true);
//echo $pkey;

$certKey = LetsEncrypt::generatePrivateKey(true);
$csrTemplate = getOption('defaultCsrTemplate');
$csr = LetsEncrypt::generateCSR(
    $certKey,
    'vn4.ru',
    getOption('adminEmail'),
    $csrTemplate['countryName'],
    $csrTemplate['stateOrProvinceName'],
    $csrTemplate['localityName'],
    $csrTemplate['organizationName'],
    $csrTemplate['organizationalUnitName']
);
echo $csr;
