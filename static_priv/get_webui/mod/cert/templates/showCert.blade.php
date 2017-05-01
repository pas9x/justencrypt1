@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_cert.css' media='all'>
  <script type='text/javascript' src='/js/mod_cert.js'></script>
@append

@section('modcontent')
  <h3 class='cool'>Параметры сертификата</h3>

  <table class='form'>
    <tr><td>ID:</td><td>{{ $idCert }}</td></tr>
    <tr><td>Домен:</td><td>{{ $domain }}</td></tr>
    <tr><td>Дата выпуска:</td><td>{{ date('d.m.Y', $issuedTimestamp) }}</td></tr>
    <tr><td>Годен до:</td><td>{{ date('d.m.Y', $expireTimestamp) }}</td></tr>
    <tr><td>Корневая директория:</td><td>{{ $documentRoot }}</td></tr>
    <tr>
      <td>Операции над сертификатом:</td>
      <td>
        <p><a href='{{ $exportsLink }}'>Конфигурации выгрузки</a></p>
        <p><a href='{{ $editCertLink }}'>Настроить верификацию</a></p>
        <p><a href='{{ $testVerifyLink }}'>Протестировать верификацию</a></p>
        <p><a href='{{ $revokeLink }}' onclick='return confirmRevoke()'>Отозвать сертификат</a></p>
        <p><a href='{{ $reissueLink }}' onclick='return confirmReissue()'>Перевыпустить сертификат</a></p>
        <p><a href='{{ $deleteLink }}' onclick='return confirmDelete()'>Удалить сертификат</a></p>
      </td>
    </tr>
    <tr><td colspan='2'>
      Сертификат домена:<br>
      <textarea cols='67' rows='10' readonly>{{ $certDomain }}</textarea>
    </td></tr>
@if ($certIssuer)
    <tr><td colspan='2'>
      Сертификат родительского удостоверяющего центра:<br>
      <textarea cols='67' rows='10' readonly>{{ $certIssuer }}</textarea>
    </td></tr>
@endif
    <tr><td colspan='2'>
      Приватный ключ:<br>
      <textarea cols='67' rows='10' readonly>{{ $privateKey }}</textarea>
    </td></tr>
    <tr><td colspan='2'>
      CSR-запрос:<br>
      <textarea cols='67' rows='10' readonly>{{ $csr }}</textarea>
    </td></tr>
  </table>
@stop